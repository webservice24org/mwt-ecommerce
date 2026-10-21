<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerOrderDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_order_details(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create();

        $response = $this->get(
            route(
                'customer.orders.show',
                $order->id,
            ),
        );

        $response->assertRedirect(
            route(
                'login',
            ),
        );
    }

    public function test_unverified_customer_cannot_view_order_details(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response->assertRedirect(
            route(
                'verification.notice',
            ),
        );
    }

    public function test_customer_can_view_own_order(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'order_number' => 'ORD-CUSTOMER-001',

                'status' => OrderStatus::Pending,

                'subtotal' => 20000,

                'discount_total' => 1000,

                'delivery_total' => 500,

                'tax_total' => 0,

                'grand_total' => 19500,

                'placed_at' => now(),
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Customer/Orders/Show',
                    )
                    ->where(
                        'order.id',
                        $order->id,
                    )
                    ->where(
                        'order.order_number',
                        'ORD-CUSTOMER-001',
                    )
                    ->where(
                        'order.status.value',
                        OrderStatus::Pending
                            ->value,
                    )
                    ->where(
                        'order.subtotal',
                        20000,
                    )
                    ->where(
                        'order.discount_total',
                        1000,
                    )
                    ->where(
                        'order.delivery_total',
                        500,
                    )
                    ->where(
                        'order.grand_total',
                        19500,
                    ),
            );
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $user = User::factory()
            ->create();

        $otherUser = User::factory()
            ->create();

        $otherOrder = Order::factory()
            ->for($otherUser)
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $otherOrder->id,
                ),
            );

        $response->assertNotFound();
    }

    public function test_unknown_order_returns_not_found(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    999999999,
                ),
            );

        $response->assertNotFound();
    }

    public function test_non_numeric_order_identifier_returns_not_found(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                '/account/orders/not-an-order',
            );

        $response->assertNotFound();
    }

    public function test_customer_cannot_infer_another_customers_order_from_response(): void
    {
        $user = User::factory()
            ->create();

        $otherUser = User::factory()
            ->create();

        $otherOrder = Order::factory()
            ->for($otherUser)
            ->create([
                'order_number' => 'ORD-SECRET-999',
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $otherOrder->id,
                ),
            );

        $response
            ->assertNotFound()
            ->assertDontSee(
                'ORD-SECRET-999',
            );
    }

    public function test_customer_order_details_include_ordered_item_snapshots(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'subtotal' => 25000,

                'grand_total' => 25000,
            ]);

        $item = $order
            ->items()
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'Historical Product',

                'sku' => 'OLD-SKU-001',

                'variant_description' => 'Size: Large / Black',

                'unit_price' => 12500,

                'quantity' => 2,

                'line_total' => 25000,
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->has(
                        'order.items',
                        1,
                    )
                    ->where(
                        'order.items.0.id',
                        $item->id,
                    )
                    ->where(
                        'order.items.0.product_name',
                        'Historical Product',
                    )
                    ->where(
                        'order.items.0.sku',
                        'OLD-SKU-001',
                    )
                    ->where(
                        'order.items.0.variant_description',
                        'Size: Large / Black',
                    )
                    ->where(
                        'order.items.0.unit_price',
                        12500,
                    )
                    ->where(
                        'order.items.0.quantity',
                        2,
                    )
                    ->where(
                        'order.items.0.line_total',
                        25000,
                    ),
            );
    }

    public function test_order_item_snapshot_remains_visible_without_catalog_records(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create();

        $order
            ->items()
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'Discontinued Product',

                'sku' => 'ARCHIVE-001',

                'variant_description' => 'Original ordered variant',

                'unit_price' => 9000,

                'quantity' => 1,

                'line_total' => 9000,
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.items.0.product_id',
                        null,
                    )
                    ->where(
                        'order.items.0.variant_id',
                        null,
                    )
                    ->where(
                        'order.items.0.product_name',
                        'Discontinued Product',
                    )
                    ->where(
                        'order.items.0.sku',
                        'ARCHIVE-001',
                    ),
            );
    }

    public function test_order_item_display_uses_snapshot_values_not_current_catalog_values(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'Current Product Name',

                'sku' => 'CURRENT-SKU',
            ]);

        $order = Order::factory()
            ->for($user)
            ->create();

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => 'Original Ordered Name',

                'sku' => 'ORIGINAL-SKU',

                'variant_description' => null,

                'unit_price' => 15000,

                'quantity' => 1,

                'line_total' => 15000,
            ]);

        /*
         * Catalog changes after the order was placed.
         */
        $product->update([
            'name' => 'Renamed Product',

            'sku' => 'RENAMED-SKU',
        ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.items.0.product_name',
                        'Original Ordered Name',
                    )
                    ->where(
                        'order.items.0.sku',
                        'ORIGINAL-SKU',
                    ),
            );
    }

    public function test_customer_order_details_include_delivery_address_snapshot(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'delivery_recipient_name' => 'John Customer',

                'delivery_phone' => '01700000000',

                'delivery_address_line_1' => '12 Example Road',

                'delivery_address_line_2' => 'Apartment 4B',

                'delivery_city' => 'Dhaka',

                'delivery_state_or_region' => 'Dhaka Division',

                'delivery_postal_code' => '1207',

                'delivery_country_code' => 'BD',
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.delivery_address.recipient_name',
                        'John Customer',
                    )
                    ->where(
                        'order.delivery_address.phone',
                        '01700000000',
                    )
                    ->where(
                        'order.delivery_address.address_line_1',
                        '12 Example Road',
                    )
                    ->where(
                        'order.delivery_address.address_line_2',
                        'Apartment 4B',
                    )
                    ->where(
                        'order.delivery_address.city',
                        'Dhaka',
                    )
                    ->where(
                        'order.delivery_address.state_or_region',
                        'Dhaka Division',
                    )
                    ->where(
                        'order.delivery_address.postal_code',
                        '1207',
                    )
                    ->where(
                        'order.delivery_address.country_code',
                        'BD',
                    ),
            );
    }

    public function test_order_delivery_snapshot_does_not_change_when_saved_address_changes(): void
    {
        $user = User::factory()
            ->create();

        $address = $user
            ->addresses()
            ->create([
                'label' => 'Home',

                'recipient_name' => 'Original Recipient',

                'phone' => '01711111111',

                'address_line_1' => 'Original Address',

                'address_line_2' => null,

                'city' => 'Dhaka',

                'state_or_region' => null,

                'postal_code' => null,

                'country_code' => 'BD',

                'is_default' => true,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'delivery_address_id' => $address->id,

                'delivery_recipient_name' => 'Original Recipient',

                'delivery_phone' => '01711111111',

                'delivery_address_line_1' => 'Original Address',

                'delivery_address_line_2' => null,

                'delivery_city' => 'Dhaka',

                'delivery_state_or_region' => null,

                'delivery_postal_code' => null,

                'delivery_country_code' => 'BD',
            ]);

        $address->update([
            'recipient_name' => 'Changed Recipient',

            'phone' => '01899999999',

            'address_line_1' => 'Changed Address',
        ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.delivery_address.recipient_name',
                        'Original Recipient',
                    )
                    ->where(
                        'order.delivery_address.phone',
                        '01711111111',
                    )
                    ->where(
                        'order.delivery_address.address_line_1',
                        'Original Address',
                    ),
            );
    }

    public function test_order_delivery_snapshot_remains_available_after_saved_address_is_deleted(): void
    {
        $user = User::factory()
            ->create();

        $address = $user
            ->addresses()
            ->create([
                'label' => 'Home',

                'recipient_name' => 'Snapshot Recipient',

                'phone' => '01722222222',

                'address_line_1' => 'Snapshot Address',

                'address_line_2' => null,

                'city' => 'Dhaka',

                'state_or_region' => null,

                'postal_code' => null,

                'country_code' => 'BD',

                'is_default' => true,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'delivery_address_id' => $address->id,

                'delivery_recipient_name' => 'Snapshot Recipient',

                'delivery_phone' => '01722222222',

                'delivery_address_line_1' => 'Snapshot Address',

                'delivery_address_line_2' => null,

                'delivery_city' => 'Dhaka',

                'delivery_state_or_region' => null,

                'delivery_postal_code' => null,

                'delivery_country_code' => 'BD',
            ]);

        $address->delete();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.delivery_address.recipient_name',
                        'Snapshot Recipient',
                    )
                    ->where(
                        'order.delivery_address.address_line_1',
                        'Snapshot Address',
                    ),
            );
    }

    public function test_customer_order_details_include_separate_order_payment_and_fulfilment_states(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Confirmed,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.status.value',
                        OrderStatus::Confirmed
                            ->value,
                    )
                    ->where(
                        'order.status.label',
                        OrderStatus::Confirmed
                            ->label(),
                    )
                    ->where(
                        'order.payment.method.value',
                        PaymentMethod::CashOnDelivery
                            ->value,
                    )
                    ->where(
                        'order.payment.method.label',
                        PaymentMethod::CashOnDelivery
                            ->label(),
                    )
                    ->where(
                        'order.payment.status.value',
                        PaymentStatus::Unpaid
                            ->value,
                    )
                    ->where(
                        'order.payment.status.label',
                        PaymentStatus::Unpaid
                            ->label(),
                    )
                    ->where(
                        'order.fulfilment_status.value',
                        FulfilmentStatus::Unfulfilled
                            ->value,
                    )
                    ->where(
                        'order.fulfilment_status.label',
                        FulfilmentStatus::Unfulfilled
                            ->label(),
                    ),
            );
    }

    public function test_order_payment_and_fulfilment_states_are_not_derived_from_each_other(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Paid,

                'fulfilment_status' => FulfilmentStatus::Shipped,
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.status.value',
                        'processing',
                    )
                    ->where(
                        'order.payment.status.value',
                        'paid',
                    )
                    ->where(
                        'order.fulfilment_status.value',
                        'shipped',
                    ),
            );
    }
}
