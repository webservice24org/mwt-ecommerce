<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_product_stock_is_decremented_when_order_is_created(): void
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
                    quantity: 3,
                    addressId: $address->id,
                ),
            );

        app(
            CreateOrderAction::class,
        )->execute(
            $user,
        );

        $product->refresh();

        $this->assertSame(
            7,
            $product->stock_quantity,
        );
    }

    public function test_simple_product_order_fails_when_stock_is_insufficient(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(2)
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
                    quantity: 3,
                    addressId: $address->id,
                ),
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
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cart',
                $exception->errors(),
            );
        }

        $product->refresh();

        $this->assertSame(
            2,
            $product->stock_quantity,
        );

        $this->assertDatabaseCount(
            'orders',
            0,
        );

        $this->assertDatabaseCount(
            'order_items',
            0,
        );
    }

    public function test_non_inventory_product_does_not_decrement_stock(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->create([
                'price' => 10000,

                'track_inventory' => false,

                'stock_quantity' => 0,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession(
                $this->checkoutSession(
                    productId: $product->id,
                    quantity: 5,
                    addressId: $address->id,
                ),
            );

        app(
            CreateOrderAction::class,
        )->execute(
            $user,
        );

        $product->refresh();

        $this->assertSame(
            0,
            $product->stock_quantity,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );
    }

    public function test_variable_product_decrements_variant_stock(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->variable()
            ->create([
                'track_inventory' => true,
            ]);

        $variant = ProductVariant::factory()
            ->for($product)
            ->withStock(8)
            ->create([
                'price' => 15000,

                'is_active' => true,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession(
                $this->variantCheckoutSession(
                    productId: $product->id,
                    variantId: $variant->id,
                    quantity: 3,
                    addressId: $address->id,
                ),
            );

        app(
            CreateOrderAction::class,
        )->execute(
            $user,
        );

        $variant->refresh();
        $product->refresh();

        $this->assertSame(
            5,
            $variant->stock_quantity,
        );

        /*
         * Variable stock belongs to the variant,
         * not to products.stock_quantity.
         */
        $this->assertSame(
            0,
            $product->stock_quantity,
        );
    }

    public function test_variable_product_order_fails_when_variant_stock_is_insufficient(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->variable()
            ->create([
                'track_inventory' => true,
            ]);

        $variant = ProductVariant::factory()
            ->for($product)
            ->withStock(1)
            ->create([
                'price' => 15000,

                'is_active' => true,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession(
                $this->variantCheckoutSession(
                    productId: $product->id,
                    variantId: $variant->id,
                    quantity: 2,
                    addressId: $address->id,
                ),
            );

        $this->expectException(
            ValidationException::class,
        );

        try {
            app(
                CreateOrderAction::class,
            )->execute(
                $user,
            );
        } finally {
            $variant->refresh();

            $this->assertSame(
                1,
                $variant->stock_quantity,
            );

            $this->assertDatabaseCount(
                'orders',
                0,
            );
        }
    }

    public function test_competing_simple_product_checkouts_cannot_oversell_last_stock(): void
    {
        $firstUser = User::factory()
            ->create();

        $firstAddress = Address::factory()
            ->for($firstUser)
            ->default()
            ->create();

        $secondUser = User::factory()
            ->create();

        $secondAddress = Address::factory()
            ->for($secondUser)
            ->default()
            ->create();

        /*
         * Only one unit exists.
         *
         * Both customers conceptually reach checkout while
         * this single unit is still available.
         */
        $product = Product::factory()
            ->published()
            ->withStock(1)
            ->create([
                'price' => 20000,
            ]);

        /*
         * First checkout wins the stock.
         */
        $this
            ->actingAs(
                $firstUser,
            )
            ->withSession(
                $this->checkoutSession(
                    productId: $product->id,
                    quantity: 1,
                    addressId: $firstAddress->id,
                ),
            );

        $firstOrder = app(
            CreateOrderAction::class,
        )->execute(
            $firstUser,
            'stock-race-simple-first',
        );

        $product->refresh();

        $this->assertSame(
            0,
            $product->stock_quantity,
        );

        /*
         * The second customer had a valid cart based on
         * earlier stock state, but order creation MUST
         * revalidate the current locked stock.
         */
        $this
            ->actingAs(
                $secondUser,
            )
            ->withSession(
                $this->checkoutSession(
                    productId: $product->id,
                    quantity: 1,
                    addressId: $secondAddress->id,
                ),
            );

        try {
            app(
                CreateOrderAction::class,
            )->execute(
                $secondUser,
                'stock-race-simple-second',
            );

            $this->fail(
                'The second checkout should fail because the first checkout consumed the last unit.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cart',
                $exception->errors(),
            );
        }

        $product->refresh();

        /*
         * Stock must never become negative.
         */
        $this->assertSame(
            0,
            $product->stock_quantity,
        );

        /*
         * Only the winning checkout persisted an order.
         */
        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'order_items',
            1,
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $firstOrder->id,

                'user_id' => $firstUser->id,
            ],
        );

        $this->assertDatabaseMissing(
            'orders',
            [
                'user_id' => $secondUser->id,
            ],
        );

        /*
         * A failed order attempt must not destroy the
         * losing customer's cart.
         */
        $this->assertNotNull(
            session(
                'storefront.cart',
            ),
        );
    }

    public function test_competing_variant_checkouts_cannot_oversell_last_variant_stock(): void
    {
        $firstUser = User::factory()
            ->create();

        $firstAddress = Address::factory()
            ->for($firstUser)
            ->default()
            ->create();

        $secondUser = User::factory()
            ->create();

        $secondAddress = Address::factory()
            ->for($secondUser)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->variable()
            ->create([
                'track_inventory' => true,
            ]);

        /*
         * Only one unit of this exact variant exists.
         */
        $variant = ProductVariant::factory()
            ->for($product)
            ->withStock(1)
            ->create([
                'price' => 25000,

                'is_active' => true,
            ]);

        /*
         * First checkout consumes the only variant unit.
         */
        $this
            ->actingAs(
                $firstUser,
            )
            ->withSession(
                $this->variantCheckoutSession(
                    productId: $product->id,
                    variantId: $variant->id,
                    quantity: 1,
                    addressId: $firstAddress->id,
                ),
            );

        $firstOrder = app(
            CreateOrderAction::class,
        )->execute(
            $firstUser,
            'stock-race-variant-first',
        );

        $variant->refresh();

        $this->assertSame(
            0,
            $variant->stock_quantity,
        );

        /*
         * Second checkout must see the latest variant stock
         * during final order creation.
         */
        $this
            ->actingAs(
                $secondUser,
            )
            ->withSession(
                $this->variantCheckoutSession(
                    productId: $product->id,
                    variantId: $variant->id,
                    quantity: 1,
                    addressId: $secondAddress->id,
                ),
            );

        try {
            app(
                CreateOrderAction::class,
            )->execute(
                $secondUser,
                'stock-race-variant-second',
            );

            $this->fail(
                'The second checkout should fail because the first checkout consumed the last variant unit.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cart',
                $exception->errors(),
            );
        }

        $variant->refresh();
        $product->refresh();

        $this->assertSame(
            0,
            $variant->stock_quantity,
        );

        /*
         * Variable stock remains on the variant.
         */
        $this->assertSame(
            0,
            $product->stock_quantity,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'order_items',
            1,
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $firstOrder->id,

                'user_id' => $firstUser->id,
            ],
        );

        $this->assertDatabaseMissing(
            'orders',
            [
                'user_id' => $secondUser->id,
            ],
        );

        $this->assertNotNull(
            session(
                'storefront.cart',
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

    /**
     * @return array<string, mixed>
     */
    private function variantCheckoutSession(
        int $productId,
        int $variantId,
        int $quantity,
        int $addressId,
    ): array {
        return [
            'storefront.cart' => [
                'product:'
                .$productId
                .':variant:'
                .$variantId => [
                    'product_id' => $productId,

                    'variant_id' => $variantId,

                    'quantity' => $quantity,
                ],
            ],

            'storefront.checkout.address_id' => $addressId,

            'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                ->value,
        ];
    }
}
