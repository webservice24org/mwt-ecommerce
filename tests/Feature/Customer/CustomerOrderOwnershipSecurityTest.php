<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerOrderOwnershipSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customer_order_list(): void
    {
        $response = $this->get(
            route(
                'customer.orders.index',
            ),
        );

        $response->assertRedirect(
            route(
                'login',
            ),
        );
    }

    public function test_guest_cannot_access_customer_order_details(): void
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

    public function test_unverified_customer_cannot_access_order_list(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.orders.index',
                ),
            );

        $response->assertRedirect(
            route(
                'verification.notice',
            ),
        );
    }

    public function test_unverified_customer_cannot_access_order_details(): void
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

    public function test_customer_order_list_contains_only_authenticated_customers_orders(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $ownOrder = Order::factory()
            ->for($customer)
            ->create([
                'order_number' => 'ORD-OWN-001',
            ]);

        Order::factory()
            ->for($otherCustomer)
            ->create([
                'order_number' => 'ORD-OTHER-SECRET',
            ]);

        $response = $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.orders.index',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Customer/Orders/Index',
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
                        $ownOrder->id,
                    )
                    ->where(
                        'orders.data.0.order_number',
                        'ORD-OWN-001',
                    ),
            );

        $response->assertDontSee(
            'ORD-OTHER-SECRET',
        );
    }

    public function test_customer_can_view_owned_order(): void
    {
        $customer = User::factory()
            ->create();

        $order = Order::factory()
            ->for($customer)
            ->create([
                'order_number' => 'ORD-OWNED-DETAIL',

                'status' => OrderStatus::Processing,

                'payment_status' => PaymentStatus::Paid,

                'fulfilment_status' => FulfilmentStatus::Shipped,
            ]);

        $response = $this
            ->actingAs(
                $customer,
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
                        'ORD-OWNED-DETAIL',
                    )
                    ->where(
                        'order.status.value',
                        OrderStatus::Processing
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

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $otherOrder = Order::factory()
            ->for($otherCustomer)
            ->create();

        $response = $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $otherOrder->id,
                ),
            );

        $response->assertNotFound();
    }

    public function test_cross_customer_order_request_does_not_leak_order_number(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $otherOrder = Order::factory()
            ->for($otherCustomer)
            ->create([
                'order_number' => 'ORD-PRIVATE-999',
            ]);

        $response = $this
            ->actingAs(
                $customer,
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
                'ORD-PRIVATE-999',
            );
    }

    public function test_cross_customer_order_request_does_not_leak_order_item_snapshots(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $otherOrder = Order::factory()
            ->for($otherCustomer)
            ->create();

        $otherOrder
            ->items()
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'PRIVATE CUSTOMER PRODUCT',

                'sku' => 'PRIVATE-SKU-987',

                'variant_description' => 'Secret Variant',

                'unit_price' => 12500,

                'quantity' => 2,

                'line_total' => 25000,
            ]);

        $response = $this
            ->actingAs(
                $customer,
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
                'PRIVATE CUSTOMER PRODUCT',
            )
            ->assertDontSee(
                'PRIVATE-SKU-987',
            )
            ->assertDontSee(
                'Secret Variant',
            );
    }

    public function test_cross_customer_order_request_does_not_leak_delivery_snapshot(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $otherOrder = Order::factory()
            ->for($otherCustomer)
            ->create([
                'delivery_recipient_name' => 'Private Recipient',

                'delivery_phone' => '01799999999',

                'delivery_address_line_1' => 'Private Delivery Address',

                'delivery_city' => 'Private City',
            ]);

        $response = $this
            ->actingAs(
                $customer,
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
                'Private Recipient',
            )
            ->assertDontSee(
                '01799999999',
            )
            ->assertDontSee(
                'Private Delivery Address',
            )
            ->assertDontSee(
                'Private City',
            );
    }

    public function test_unknown_order_id_returns_not_found(): void
    {
        $customer = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $customer,
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
        $customer = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $customer,
            )
            ->get(
                '/account/orders/not-an-order-id',
            );

        $response->assertNotFound();
    }

    public function test_sequential_order_ids_cannot_be_used_to_browse_other_customers_orders(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $ownOrder = Order::factory()
            ->for($customer)
            ->create();

        $otherOrder = Order::factory()
            ->for($otherCustomer)
            ->create();

        $ownResponse = $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $ownOrder->id,
                ),
            );

        $otherResponse = $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $otherOrder->id,
                ),
            );

        $ownResponse->assertOk();

        $otherResponse->assertNotFound();
    }
}
