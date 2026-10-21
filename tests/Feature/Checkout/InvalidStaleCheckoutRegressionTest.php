<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvalidStaleCheckoutRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_cannot_create_order(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }

    public function test_customer_cannot_checkout_using_another_customers_address(): void
    {
        $user = User::factory()
            ->create();

        $otherUser = User::factory()
            ->create();

        $otherAddress = Address::factory()
            ->for($otherUser)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'Ownership Regression Product',

                'sku' => 'STALE-ADDRESS-001',

                'price' => 25000,

                'status' => 'published',
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $otherAddress->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }

    public function test_deleted_selected_address_cannot_create_order(): void
    {
        [
            'user' => $user,
            'address' => $address,
            'product' => $product,
        ] = $this->prepareCheckout();

        $address->delete();

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }

    public function test_unpublished_product_cannot_be_ordered_from_stale_cart(): void
    {
        [
            'user' => $user,
            'address' => $address,
            'product' => $product,
        ] = $this->prepareCheckout();

        $product->update([
            'status' => 'draft',
        ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }

    public function test_deleted_product_cannot_be_ordered_from_stale_cart(): void
    {
        [
            'user' => $user,
            'address' => $address,
            'product' => $product,
        ] = $this->prepareCheckout();

        $productId = $product->id;

        $product->delete();

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$productId => [
                        'product_id' => $productId,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }

    public function test_stale_product_price_is_recalculated_server_side(): void
    {
        [
            'user' => $user,
            'address' => $address,
            'product' => $product,
        ] = $this->prepareCheckout();

        /*
         * Product price changes after the customer
         * originally built the cart.
         */
        $product->update([
            'price' => 40000,
        ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 2,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $response->assertRedirect();

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $item = $order
            ->items()
            ->firstOrFail();

        $this->assertSame(
            40000,
            $item->unit_price,
        );

        $this->assertSame(
            80000,
            $item->line_total,
        );

        $this->assertSame(
            80000,
            $order->subtotal,
        );

        $this->assertSame(
            80000,
            $order->grand_total,
        );
    }

    /**
     * @return array{
     *     user: User,
     *     address: Address,
     *     product: Product
     * }
     */
    private function prepareCheckout(): array
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'recipient_name' => 'Stale Checkout Customer',

                'phone' => '+8801700000000',

                'address_line_1' => '123 Regression Road',

                'city' => 'Dhaka',

                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'name' => 'Stale Checkout Product',

                'sku' => 'STALE-CHECKOUT-001',

                'price' => 25000,

                'status' => 'published',
            ]);

        return [
            'user' => $user,

            'address' => $address,

            'product' => $product,
        ];
    }

    public function test_missing_selected_address_falls_back_to_customers_default_address(): void
    {
        [
            'user' => $user,
            'address' => $address,
            'product' => $product,
        ] = $this->prepareCheckout();

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                /*
                 * Deliberately no
                 * storefront.checkout.address_id.
                 *
                 * CheckoutSessionService must resolve
                 * the customer's default address.
                 */
                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'customer.orders.show',
                $order->id,
            ),
        );

        $this->assertSame(
            $address->id,
            $order->delivery_address_id,
        );

        $this->assertSame(
            $address->id,
            session(
                'storefront.checkout.address_id',
            ),
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );
    }

    public function test_customer_with_no_delivery_address_cannot_create_order(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'No Address Product',

                'sku' => 'NO-ADDRESS-001',

                'price' => 25000,

                'status' => 'published',
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }
}
