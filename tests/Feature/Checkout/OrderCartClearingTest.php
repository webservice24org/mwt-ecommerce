<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class OrderCartClearingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_is_cleared_after_successful_order_creation(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(10)
            ->create([
                'price' => 10000,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession(
                $this->checkoutSession(
                    productId: $product->id,

                    quantity: 2,

                    addressId: $address->id,
                ),
            );

        $this->assertNotEmpty(
            session(
                'storefront.cart',
            ),
        );

        $order = app(
            CreateOrderAction::class,
        )->execute(
            $user,
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
            ],
        );

        $this->assertNull(
            session(
                'storefront.cart',
            ),
        );
    }

    public function test_cart_remains_when_stock_validation_fails(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(1)
            ->create([
                'price' => 10000,
            ]);

        $session =
            $this->checkoutSession(
                productId: $product->id,

                quantity: 2,

                addressId: $address->id,
            );

        $this
            ->actingAs(
                $user,
            )
            ->withSession(
                $session,
            );

        try {
            app(
                CreateOrderAction::class,
            )->execute(
                $user,
            );

            $this->fail(
                'Order creation should fail when stock is insufficient.',
            );
        } catch (
            ValidationException
        ) {
            //
        }

        $this->assertSame(
            $session[
                'storefront.cart'
            ],
            session(
                'storefront.cart',
            ),
        );

        $this->assertDatabaseCount(
            'orders',
            0,
        );

        $this->assertDatabaseCount(
            'order_items',
            0,
        );

        $product->refresh();

        $this->assertSame(
            1,
            $product->stock_quantity,
        );
    }

    public function test_cart_remains_when_checkout_validation_fails(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(5)
            ->create([
                'price' => 10000,
            ]);

        /*
         * Deliberately no checkout delivery
         * address is provided.
         */
        $session = [
            'storefront.cart' => [
                'product:'.$product->id => [
                    'product_id' => $product->id,

                    'variant_id' => null,

                    'quantity' => 1,
                ],
            ],

            'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                ->value,
        ];

        $this
            ->actingAs(
                $user,
            )
            ->withSession(
                $session,
            );

        try {
            app(
                CreateOrderAction::class,
            )->execute(
                $user,
            );

            $this->fail(
                'Checkout without a delivery address should fail validation.',
            );
        } catch (
            ValidationException
        ) {
            //
        }

        $this->assertSame(
            $session[
                'storefront.cart'
            ],
            session(
                'storefront.cart',
            ),
        );

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }

    public function test_successful_order_does_not_clear_checkout_address_or_payment_state(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(5)
            ->create([
                'price' => 10000,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession(
                $this->checkoutSession(
                    productId: $product->id,

                    quantity: 1,

                    addressId: $address->id,
                ),
            );

        app(
            CreateOrderAction::class,
        )->execute(
            $user,
        );

        $this->assertNull(
            session(
                'storefront.cart',
            ),
        );

        /*
         * 6.4F is specifically cart cleanup.
         *
         * Checkout preference cleanup can be
         * handled separately if required by the
         * final order/confirmation workflow.
         */
        $this->assertSame(
            $address->id,
            session(
                'storefront.checkout.address_id',
            ),
        );

        $this->assertSame(
            PaymentMethod::CashOnDelivery
                ->value,
            session(
                'storefront.checkout.payment_method',
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutSession(
        int $productId,
        int $quantity,
        int $addressId,
    ): array {
        return [
            'storefront.cart' => [
                'product:'.$productId => [
                    'product_id' => $productId,

                    'variant_id' => null,

                    'quantity' => $quantity,
                ],
            ],

            'storefront.checkout.address_id' => $addressId,

            'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                ->value,
        ];
    }
}
