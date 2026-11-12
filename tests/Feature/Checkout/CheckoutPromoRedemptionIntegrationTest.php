<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Promotion\Enums\DiscountType;
use App\Models\Address;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CheckoutPromoRedemptionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_order_redeems_selected_promo(): void
    {
        [
            $user,
            $product,
            $shippingRate,
        ] = $this->checkoutContext();

        $promoCode =
            $this->promoCode();

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.
                    $product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.selected_promo_code_id' => $promoCode->id,

                'storefront.checkout.address_id' => $user
                    ->addresses()
                    ->firstOrFail()
                    ->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $order =
            app(
                CreateOrderAction::class,
            )->execute(
                user: $user,

                shippingRateId: $shippingRate
                    ->id,
            );

        /*
         * BDT 1,000 product represented in
         * minor units.
         *
         * 10% = BDT 100 discount.
         */
        $this->assertSame(
            100000,
            $order->subtotal,
        );

        $this->assertSame(
            10000,
            $order->discount_total,
        );

        $this->assertSame(
            90000,
            $order->grand_total,
        );

        $this->assertDatabaseHas(
            'promo_code_usages',
            [
                'promo_code_id' => $promoCode->id,

                'user_id' => $user->id,

                'order_id' => $order->id,

                'code' => 'CHECKOUT10',

                'discount_amount' => 10000,
            ],
        );

        $this->assertSame(
            1,
            $promoCode
                ->fresh()
                ?->redeemed_count,
        );
    }

    public function test_duplicate_submission_does_not_redeem_promo_twice(): void
    {
        [
            $user,
            $product,
            $shippingRate,
        ] = $this->checkoutContext();

        $promoCode =
            $this->promoCode();

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.
                    $product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.selected_promo_code_id' => $promoCode->id,

                'storefront.checkout.address_id' => $user
                    ->addresses()
                    ->firstOrFail()
                    ->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $checkoutSession =
            app(
                CheckoutSessionService::class,
            );

        $token =
            $checkoutSession
                ->orderSubmissionToken();

        $action =
            app(
                CreateOrderAction::class,
            );

        $first =
            $action->execute(
                user: $user,

                shippingRateId: $shippingRate->id,

                idempotencyKey: $token,
            );

        /*
         * First successful order clears the cart, but
         * the CreateOrderAction idempotency fast path
         * must return the already-created order before
         * checkout is validated again.
         */
        $second =
            $action->execute(
                user: $user,

                shippingRateId: $shippingRate->id,

                idempotencyKey: $token,
            );

        $this->assertSame(
            $first->id,
            $second->id,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'promo_code_usages',
            1,
        );

        $this->assertSame(
            1,
            $promoCode
                ->fresh()
                ?->redeemed_count,
        );
    }

    public function test_order_without_promo_creates_no_usage_record(): void
    {
        [
            $user,
            $product,
            $shippingRate,
        ] = $this->checkoutContext();

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.
                    $product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $user
                    ->addresses()
                    ->firstOrFail()
                    ->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $order =
            app(
                CreateOrderAction::class,
            )->execute(
                user: $user,

                shippingRateId: $shippingRate
                    ->id,
            );

        $this->assertSame(
            0,
            $order
                ->discount_total,
        );

        $this->assertDatabaseCount(
            'promo_code_usages',
            0,
        );
    }

    /**
     * @return array{
     *     0: User,
     *     1: Product,
     *     2: ShippingRate
     * }
     */
    private function checkoutContext(): array
    {
        $user =
            User::factory()
                ->create();

        Address::factory()
            ->for(
                $user,
            )
            ->default()
            ->create();

        $product =
            Product::factory()
                ->published()
                ->withStock(
                    10,
                )
                ->create([
                    /*
                     * BDT 1,000 in minor units.
                     */
                    'price' => 100000,
                ]);

        $shippingRate =
            ShippingRate::factory()
                ->create([
                    'amount' => 0,

                    'is_active' => true,
                ]);

        return [
            $user,
            $product,
            $shippingRate,
        ];
    }

    private function promoCode(): PromoCode
    {
        return PromoCode::query()
            ->create([
                'promotion_id' => null,

                'code' => 'CHECKOUT10',

                'name' => 'Checkout 10%',

                'description' => null,

                'discount_type' => DiscountType::Percentage,

                /*
                 * 1000 basis points = 10%.
                 */
                'discount_value' => 1000,

                'maximum_discount' => null,

                'minimum_subtotal' => null,

                'usage_limit' => 10,

                'usage_limit_per_customer' => 2,

                'redeemed_count' => 0,

                'starts_at' => null,

                'ends_at' => null,

                'is_active' => true,

                'is_stackable' => false,
            ]);
    }
}
