<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Domain\Cart\Services\CartPromoSession;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CartPromoCodeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        session()->flush();
    }

    public function test_guest_can_apply_valid_promo_code(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $promo =
            $this->promoCode([
                'code' => 'SAVE20',

                'discount_type' => 'percentage',

                'discount_value' => 2000,
            ]);

        $response =
            $this->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'save20',
                ],
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $this->assertSame(
            $promo->id,
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_authenticated_customer_can_apply_valid_promo_code(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $promo =
            $this->promoCode([
                'code' => 'CUSTOMER10',
            ]);

        $this
            ->actingAs(
                $customer,
                'web',
            )
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'CUSTOMER10',
                ],
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $this->assertSame(
            $promo->id,
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_code_is_normalized_before_lookup(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $promo =
            $this->promoCode([
                'code' => 'SAVE20',
            ]);

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => '  save20  ',
                ],
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $this->assertSame(
            $promo->id,
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_invalid_code_is_rejected(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'DOESNOTEXIST',
                ],
            )
            ->assertSessionHasErrors([
                'promo_code',
            ]);

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_promo_cannot_be_applied_to_empty_cart(): void
    {
        $this->promoCode([
            'code' => 'SAVE20',
        ]);

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'SAVE20',
                ],
            )
            ->assertSessionHasErrors([
                'promo_code',
            ]);

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_inactive_code_is_rejected(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $this->promoCode([
            'code' => 'OFF20',

            'is_active' => false,
        ]);

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'OFF20',
                ],
            )
            ->assertSessionHasErrors([
                'promo_code',
            ]);
    }

    public function test_expired_code_is_rejected(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $this->promoCode([
            'code' => 'EXPIRED',

            'ends_at' => CarbonImmutable::now()
                ->subMinute(),
        ]);

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'EXPIRED',
                ],
            )
            ->assertSessionHasErrors([
                'promo_code',
            ]);
    }

    public function test_future_code_is_rejected(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $this->promoCode([
            'code' => 'FUTURE',

            'starts_at' => CarbonImmutable::now()
                ->addHour(),
        ]);

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'FUTURE',
                ],
            )
            ->assertSessionHasErrors([
                'promo_code',
            ]);
    }

    public function test_minimum_subtotal_is_checked_against_server_cart(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $this->promoCode([
            'code' => 'MINIMUM',

            'minimum_subtotal' => 10000,
        ]);

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'MINIMUM',

                    /*
                     * Any client supplied totals
                     * are ignored.
                     */
                    'subtotal' => 50000,

                    'discount_total' => 50000,

                    'grand_total' => 0,
                ],
            )
            ->assertSessionHasErrors([
                'promo_code',
            ]);

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_exhausted_code_is_rejected(): void
    {
        $product =
            $this->cartProduct(
                price: 5000,
            );

        $this->putProductInCart(
            $product,
        );

        $this->promoCode([
            'code' => 'EXHAUSTED',

            'usage_limit' => 2,

            'redeemed_count' => 2,
        ]);

        $this
            ->post(
                route(
                    'cart.promo-code.store',
                ),
                [
                    'code' => 'EXHAUSTED',
                ],
            )
            ->assertSessionHasErrors([
                'promo_code',
            ]);
    }

    public function test_selected_promo_can_be_removed(): void
    {
        $promo =
            $this->promoCode();

        app(
            CartPromoSession::class,
        )->put(
            $promo->id,
        );

        $this
            ->delete(
                route(
                    'cart.promo-code.destroy',
                ),
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_removing_promo_is_idempotent(): void
    {
        $this
            ->delete(
                route(
                    'cart.promo-code.destroy',
                ),
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    private function putProductInCart(
        Product $product,
    ): void {
        session()->put(
            'storefront.cart',
            [
                "product:{$product->id}" => [
                    'product_id' => $product->id,

                    'variant_id' => null,

                    'quantity' => 1,
                ],
            ],
        );
    }

    private function cartProduct(
        int $price,
    ): Product {
        return Product::factory()
            ->create([
                'price' => $price,

                'status' => 'published',
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function promoCode(
        array $overrides = [],
    ): PromoCode {
        static $sequence = 0;

        $sequence++;

        return PromoCode::query()
            ->create(
                array_merge(
                    [
                        'promotion_id' => null,

                        'code' => "CART{$sequence}",

                        'name' => "Cart promo {$sequence}",

                        'description' => null,

                        'discount_type' => 'percentage',

                        'discount_value' => 2000,

                        'maximum_discount' => null,

                        'minimum_subtotal' => null,

                        'usage_limit' => null,

                        'usage_limit_per_customer' => null,

                        'redeemed_count' => 0,

                        'starts_at' => null,

                        'ends_at' => null,

                        'is_active' => true,

                        'is_stackable' => false,
                    ],
                    $overrides,
                ),
            );
    }
}
