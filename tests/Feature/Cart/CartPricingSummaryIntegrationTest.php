<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Domain\Cart\Services\CartPromoSession;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CartPricingSummaryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        session()->flush();
    }

    public function test_cart_show_returns_authoritative_promo_aware_totals(): void
    {
        $product =
            $this->product(
                price: 5000,
            );

        $this->putProductInCart(
            product: $product,

            quantity: 1,
        );

        $promo =
            $this->promoCode([
                'code' => 'SAVE20',

                'discount_type' => 'percentage',

                'discount_value' => 2000,
            ]);

        $this->selectPromo(
            $promo,
        );

        $this
            ->getJson(
                route(
                    'cart.show',
                ),
            )
            ->assertOk()
            ->assertJson([
                'item_count' => 1,

                'subtotal' => 5000,

                'promo' => [
                    'promo_code_id' => $promo->id,

                    'code' => 'SAVE20',

                    'discount_amount' => 1000,
                ],

                'discount_total' => 1000,

                'grand_total' => 4000,
            ]);
    }

    public function test_quantity_update_recalculates_discount_and_grand_total(): void
    {
        $product =
            $this->product(
                price: 5000,
            );

        $this->putProductInCart(
            product: $product,

            quantity: 1,
        );

        $promo =
            $this->promoCode([
                'discount_type' => 'percentage',

                'discount_value' => 2000,
            ]);

        $this->selectPromo(
            $promo,
        );

        $key =
            "product:{$product->id}";

        $this
            ->patchJson(
                route(
                    'cart.items.update',
                    [
                        'key' => $key,
                    ],
                ),
                [
                    'quantity' => 2,
                ],
            )
            ->assertOk()
            ->assertJson([
                'item_count' => 2,

                'subtotal' => 10000,

                'promo' => [
                    'promo_code_id' => $promo->id,

                    'discount_amount' => 2000,
                ],

                'discount_total' => 2000,

                'grand_total' => 8000,
            ]);
    }

    public function test_cart_change_clears_promo_when_minimum_subtotal_is_no_longer_met(): void
    {
        $first =
            $this->product(
                price: 4000,
            );

        $second =
            $this->product(
                price: 2000,
            );

        $this->putProductsInCart([
            $first->id => 1,
            $second->id => 1,
        ]);

        $promo =
            $this->promoCode([
                'discount_type' => 'fixed',

                'discount_value' => 500,

                'minimum_subtotal' => 5000,
            ]);

        $this->selectPromo(
            $promo,
        );

        $this
            ->deleteJson(
                route(
                    'cart.items.destroy',
                    [
                        'key' => "product:{$second->id}",
                    ],
                ),
            )
            ->assertOk()
            ->assertJson([
                'item_count' => 1,

                'subtotal' => 4000,

                'promo' => null,

                'discount_total' => 0,

                'grand_total' => 4000,
            ]);

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_removing_all_items_clears_invalid_selected_promo(): void
    {
        $product =
            $this->product(
                price: 5000,
            );

        $this->putProductInCart(
            product: $product,

            quantity: 1,
        );

        $promo =
            $this->promoCode();

        $this->selectPromo(
            $promo,
        );

        $this
            ->deleteJson(
                route(
                    'cart.items.destroy',
                    [
                        'key' => "product:{$product->id}",
                    ],
                ),
            )
            ->assertOk()
            ->assertJson([
                'item_count' => 0,

                'subtotal' => 0,

                'promo' => null,

                'discount_total' => 0,

                'grand_total' => 0,
            ]);

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_cart_clear_returns_zero_authoritative_totals_and_removes_promo(): void
    {
        $product =
            $this->product(
                price: 5000,
            );

        $this->putProductInCart(
            product: $product,

            quantity: 2,
        );

        $promo =
            $this->promoCode();

        $this->selectPromo(
            $promo,
        );

        $this
            ->deleteJson(
                route(
                    'cart.clear',
                ),
            )
            ->assertOk()
            ->assertExactJson([
                'items' => [],

                'item_count' => 0,

                'subtotal' => 0,

                'promo' => null,

                'discount_total' => 0,

                'grand_total' => 0,
            ]);

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_expired_selected_promo_is_cleared_when_cart_is_read(): void
    {
        $product =
            $this->product(
                price: 5000,
            );

        $this->putProductInCart(
            product: $product,

            quantity: 1,
        );

        $promo =
            $this->promoCode([
                'ends_at' => now()
                    ->subMinute(),
            ]);

        $this->selectPromo(
            $promo,
        );

        $this
            ->getJson(
                route(
                    'cart.show',
                ),
            )
            ->assertOk()
            ->assertJson([
                'subtotal' => 5000,

                'promo' => null,

                'discount_total' => 0,

                'grand_total' => 5000,
            ]);

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    public function test_cart_response_never_accepts_client_discount_or_total_values(): void
    {
        $product =
            $this->product(
                price: 5000,
            );

        $this->putProductInCart(
            product: $product,

            quantity: 1,
        );

        $promo =
            $this->promoCode([
                'discount_type' => 'fixed',

                'discount_value' => 500,
            ]);

        $this->selectPromo(
            $promo,
        );

        $key =
            "product:{$product->id}";

        $this
            ->patchJson(
                route(
                    'cart.items.update',
                    [
                        'key' => $key,
                    ],
                ),
                [
                    'quantity' => 1,

                    /*
                     * Deliberately malicious /
                     * client-calculated totals.
                     *
                     * UpdateCartItemRequest ignores
                     * all of these.
                     */
                    'subtotal' => 1,

                    'discount_total' => 5000,

                    'grand_total' => 0,
                ],
            )
            ->assertOk()
            ->assertJson([
                'subtotal' => 5000,

                'discount_total' => 500,

                'grand_total' => 4500,
            ]);
    }

    private function selectPromo(
        PromoCode $promoCode,
    ): void {
        app(
            CartPromoSession::class,
        )->put(
            $promoCode->id,
        );
    }

    private function putProductInCart(
        Product $product,
        int $quantity,
    ): void {
        $this->putProductsInCart([
            $product->id => $quantity,
        ]);
    }

    /**
     * @param  array<int, int>  $products
     */
    private function putProductsInCart(
        array $products,
    ): void {
        $items = [];

        foreach (
            $products as $productId => $quantity
        ) {
            $items[
                "product:{$productId}"
            ] = [
                'product_id' => $productId,

                'variant_id' => null,

                'quantity' => $quantity,
            ];
        }

        session()->put(
            'storefront.cart',
            $items,
        );
    }

    private function product(
        int $price,
    ): Product {
        return Product::factory()
            ->create([
                'price' => $price,

                'status' => 'published',

                'published_at' => now()
                    ->subMinute(),
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

                        'code' => "SUMMARY{$sequence}",

                        'name' => "Summary Promo {$sequence}",

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
