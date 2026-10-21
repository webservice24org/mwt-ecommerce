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

final class FullCheckoutRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_complete_full_cod_checkout_journey(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'recipient_name' => 'Checkout Customer',

                'phone' => '+8801700000000',

                'address_line_1' => '123 Checkout Road',

                'city' => 'Dhaka',

                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'name' => 'Regression Product',

                'sku' => 'REGRESSION-001',

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

                        'quantity' => 2,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        /*
         * 1. Checkout page is available.
         */
        $this
            ->get(
                route(
                    'checkout.index',
                ),
            )
            ->assertOk();

        /*
         * 2. Server-side checkout validation succeeds.
         */
        $this
            ->post(
                route(
                    'checkout.validate',
                ),
            )
            ->assertSessionHasNoErrors();

        /*
         * 3. Place the order.
         */
        $response = $this
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

        /*
         * 4. Customer lands on the exact new order.
         */
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

        /*
         * 5. Initial order state is correct.
         */
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

        /*
         * 6. Delivery snapshot is preserved.
         */
        $this->assertSame(
            $address->id,
            $order->delivery_address_id,
        );

        $this->assertSame(
            'Checkout Customer',
            $order->delivery_recipient_name,
        );

        $this->assertSame(
            '123 Checkout Road',
            $order->delivery_address_line_1,
        );

        $this->assertSame(
            'Dhaka',
            $order->delivery_city,
        );

        /*
         * 7. Item snapshot is correct.
         */
        $this->assertCount(
            1,
            $order->items,
        );

        $item = $order
            ->items()
            ->firstOrFail();

        $this->assertSame(
            $product->id,
            $item->product_id,
        );

        $this->assertSame(
            'Regression Product',
            $item->product_name,
        );

        $this->assertSame(
            'REGRESSION-001',
            $item->sku,
        );

        $this->assertSame(
            25000,
            $item->unit_price,
        );

        $this->assertSame(
            2,
            $item->quantity,
        );

        $this->assertSame(
            50000,
            $item->line_total,
        );

        /*
         * 8. Cart is cleared only after success.
         */
        $this->assertNull(
            session(
                'storefront.cart',
            ),
        );

        /*
         * 9. Customer can immediately view
         *    the newly-created order.
         */
        $this
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            )
            ->assertOk();

        /*
         * 10. Exactly one order was created.
         */
        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'order_items',
            1,
        );
    }
}
