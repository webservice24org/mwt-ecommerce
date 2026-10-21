<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminOrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_cannot_view_order_list(): void
    {
        $this
            ->get(
                route(
                    'admin.orders.index',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_guest_admin_cannot_view_order_details(): void
    {
        $customer = User::factory()
            ->create();

        $order = Order::factory()
            ->for($customer)
            ->create();

        $this
            ->get(
                route(
                    'admin.orders.show',
                    $order->id,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_active_admin_can_view_order_list(): void
    {
        $admin = $this
            ->createAdmin();

        $customer = User::factory()
            ->create([
                'name' => 'COD Customer',

                'email' => 'cod@example.com',
            ]);

        $order = Order::factory()
            ->for($customer)
            ->create([
                'order_number' => 'ORD-COD-ADMIN-001',

                'status' => OrderStatus::Pending,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,

                'grand_total' => 25000,
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.index',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Orders/Index',
                    )
                    ->where(
                        'orders.total',
                        1,
                    )
                    ->has(
                        'orders.data',
                        1,
                    )
                    ->where(
                        'orders.data.0.id',
                        $order->id,
                    )
                    ->where(
                        'orders.data.0.order_number',
                        'ORD-COD-ADMIN-001',
                    )
                    ->where(
                        'orders.data.0.customer.name',
                        'COD Customer',
                    )
                    ->where(
                        'orders.data.0.customer.email',
                        'cod@example.com',
                    )
                    ->where(
                        'orders.data.0.status.value',
                        OrderStatus::Pending
                            ->value,
                    )
                    ->where(
                        'orders.data.0.payment_method.value',
                        PaymentMethod::CashOnDelivery
                            ->value,
                    )
                    ->where(
                        'orders.data.0.payment_method.label',
                        PaymentMethod::CashOnDelivery
                            ->label(),
                    )
                    ->where(
                        'orders.data.0.payment_status.value',
                        PaymentStatus::Unpaid
                            ->value,
                    )
                    ->where(
                        'orders.data.0.payment_status.label',
                        PaymentStatus::Unpaid
                            ->label(),
                    )
                    ->where(
                        'orders.data.0.fulfilment_status.value',
                        FulfilmentStatus::Unfulfilled
                            ->value,
                    ),
            );
    }

    public function test_admin_can_see_cod_and_unpaid_status_on_order_details(): void
    {
        $admin = $this
            ->createAdmin();

        $customer = User::factory()
            ->create([
                'name' => 'COD Customer',

                'email' => 'customer@example.com',
            ]);

        $order = Order::factory()
            ->for($customer)
            ->create([
                'order_number' => 'ORD-COD-DETAIL-001',

                'status' => OrderStatus::Pending,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,

                'delivery_recipient_name' => 'COD Recipient',

                'delivery_phone' => '+8801700000000',

                'delivery_address_line_1' => '123 COD Road',

                'delivery_address_line_2' => 'Floor 2',

                'delivery_city' => 'Dhaka',

                'delivery_state_or_region' => 'Dhaka Division',

                'delivery_postal_code' => '1207',

                'delivery_country_code' => 'BD',

                'subtotal' => 25000,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 25000,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'Historical COD Product',

                'sku' => 'COD-SKU-001',

                'variant_description' => 'Black / Large',

                'unit_price' => 25000,

                'quantity' => 1,

                'line_total' => 25000,
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
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
                        'Admin/Orders/Show',
                    )
                    ->where(
                        'order.id',
                        $order->id,
                    )
                    ->where(
                        'order.order_number',
                        'ORD-COD-DETAIL-001',
                    )
                    ->where(
                        'order.customer.name',
                        'COD Customer',
                    )
                    ->where(
                        'order.customer.email',
                        'customer@example.com',
                    )
                    ->where(
                        'order.status.value',
                        OrderStatus::Pending
                            ->value,
                    )
                    ->where(
                        'order.status.label',
                        OrderStatus::Pending
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
                    )
                    ->where(
                        'order.delivery_address.recipient_name',
                        'COD Recipient',
                    )
                    ->where(
                        'order.delivery_address.phone',
                        '+8801700000000',
                    )
                    ->where(
                        'order.delivery_address.address_line_1',
                        '123 COD Road',
                    )
                    ->where(
                        'order.delivery_address.address_line_2',
                        'Floor 2',
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
                    )
                    ->has(
                        'order.items',
                        1,
                    )
                    ->where(
                        'order.items.0.product_name',
                        'Historical COD Product',
                    )
                    ->where(
                        'order.items.0.sku',
                        'COD-SKU-001',
                    )
                    ->where(
                        'order.items.0.variant_description',
                        'Black / Large',
                    )
                    ->where(
                        'order.items.0.unit_price',
                        25000,
                    )
                    ->where(
                        'order.items.0.quantity',
                        1,
                    )
                    ->where(
                        'order.items.0.line_total',
                        25000,
                    ),
            );
    }

    public function test_admin_order_details_keep_order_payment_and_fulfilment_states_separate(): void
    {
        $admin = $this
            ->createAdmin();

        $customer = User::factory()
            ->create();

        $order = Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Paid,

                'fulfilment_status' => FulfilmentStatus::Shipped,
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
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
                        OrderStatus::Processing
                            ->value,
                    )
                    ->where(
                        'order.payment.method.value',
                        PaymentMethod::CashOnDelivery
                            ->value,
                    )
                    ->where(
                        'order.payment.status.value',
                        PaymentStatus::Paid
                            ->value,
                    )
                    ->where(
                        'order.fulfilment_status.value',
                        FulfilmentStatus::Shipped
                            ->value,
                    ),
            );
    }

    public function test_unknown_admin_order_returns_not_found(): void
    {
        $admin = $this
            ->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
                    999999999,
                ),
            )
            ->assertNotFound();
    }

    public function test_non_numeric_admin_order_identifier_returns_not_found(): void
    {
        $admin = $this
            ->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                '/admin/orders/not-an-order',
            )
            ->assertNotFound();
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()
            ->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);
    }
}
