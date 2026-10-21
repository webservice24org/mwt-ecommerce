<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CashOnDeliveryRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_cod_checkout_creates_expected_initial_order_state(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $response = $this
            ->actingAs(
                $user,
            )
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

        $response
            ->assertRedirect(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            )
            ->assertSessionHas(
                'success',
                'Your order has been placed successfully.',
            );

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );
    }

    public function test_cod_checkout_persists_expected_database_values(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.order.store',
                ),
            )
            ->assertRedirect();

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,

                'user_id' => $user->id,

                'status' => OrderStatus::Pending
                    ->value,

                'payment_method' => PaymentMethod::CashOnDelivery
                    ->value,

                'payment_status' => PaymentStatus::Unpaid
                    ->value,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled
                    ->value,
            ],
        );
    }

    public function test_client_cannot_override_cod_payment_method(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.order.store',
                ),
                [
                    'payment_method' => 'card',
                ],
            )
            ->assertRedirect();

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );
    }

    public function test_client_cannot_mark_cod_order_as_paid(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.order.store',
                ),
                [
                    'payment_status' => 'paid',
                ],
            )
            ->assertRedirect();

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status,
        );
    }

    public function test_client_cannot_override_order_or_fulfilment_state_during_cod_checkout(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.order.store',
                ),
                [
                    'status' => 'completed',

                    'fulfilment_status' => 'delivered',
                ],
            )
            ->assertRedirect();

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );
    }

    public function test_successful_cod_checkout_clears_cart(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.order.store',
                ),
            )
            ->assertRedirect();

        $this->assertNull(
            session(
                'storefront.cart',
            ),
        );
    }

    public function test_cod_order_keeps_delivery_snapshot_after_saved_address_changes(): void
    {
        [
            'user' => $user,
            'address' => $address,
        ] = $this->prepareCheckout();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.order.store',
                ),
            )
            ->assertRedirect();

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $address->update([
            'recipient_name' => 'Changed Recipient',

            'address_line_1' => 'Changed Address',

            'city' => 'Chattogram',
        ]);

        $order->refresh();

        $this->assertSame(
            'COD Recipient',
            $order->delivery_recipient_name,
        );

        $this->assertSame(
            '123 COD Road',
            $order->delivery_address_line_1,
        );

        $this->assertSame(
            'Dhaka',
            $order->delivery_city,
        );
    }

    public function test_cod_order_keeps_item_snapshot_after_product_changes(): void
    {
        [
            'user' => $user,
            'product' => $product,
        ] = $this->prepareCheckout();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.order.store',
                ),
            )
            ->assertRedirect();

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $product->update([
            'name' => 'Changed Product',

            'sku' => 'CHANGED-SKU',

            'price' => 99999,
        ]);

        $item = $order
            ->items()
            ->firstOrFail();

        $this->assertSame(
            'COD Product',
            $item->product_name,
        );

        $this->assertSame(
            'COD-REGRESSION-001',
            $item->sku,
        );

        $this->assertSame(
            25000,
            $item->unit_price,
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
                'recipient_name' => 'COD Recipient',

                'phone' => '+8801700000000',

                'address_line_1' => '123 COD Road',

                'city' => 'Dhaka',

                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'name' => 'COD Product',

                'sku' => 'COD-REGRESSION-001',

                'price' => 25000,

                'status' => 'published',
            ]);

        $this
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
            ]);

        return [
            'user' => $user,

            'address' => $address,

            'product' => $product,
        ];
    }
}
