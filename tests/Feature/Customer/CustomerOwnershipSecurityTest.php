<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerOwnershipSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_order_index_only_contains_owned_orders(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $ownedOrder = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $otherCustomer->id,
            ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Orders/Index',
                    )
                    ->has(
                        'orders.data',
                        1,
                    )
                    ->where(
                        'orders.data.0.id',
                        $ownedOrder->id,
                    ),
            );
    }

    public function test_customer_can_view_owned_order(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Orders/Show',
                    )
                    ->where(
                        'order.id',
                        $order->id,
                    )
                    ->where(
                        'order.order_number',
                        $order->order_number,
                    ),
            );
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $otherOrder = Order::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        /*
         * Return 404 instead of 403.
         *
         * This avoids confirming that another
         * customer's order ID exists.
         */
        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.show',
                    $otherOrder,
                ),
            )
            ->assertNotFound();
    }

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $otherOrder = Order::factory()->create([
            'user_id' => $otherCustomer->id,

            'status' => OrderStatus::Pending,

            'fulfilment_status' => FulfilmentStatus::Unfulfilled,
        ]);

        $this
            ->actingAs($customer)
            ->post(
                route(
                    'customer.orders.cancel',
                    $otherOrder,
                ),
                [
                    'reason' => 'Unauthorized cancellation attempt.',
                ],
            )
            ->assertNotFound();

        $otherOrder->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $otherOrder->status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $otherOrder->fulfilment_status,
        );
    }

    public function test_guest_cannot_access_customer_orders(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->get(
                route(
                    'customer.orders.index',
                ),
            )
            ->assertRedirect(
                route('login'),
            );

        $this
            ->get(
                route(
                    'customer.orders.show',
                    $order,
                ),
            )
            ->assertRedirect(
                route('login'),
            );
    }

    public function test_unverified_customer_cannot_access_order_history(): void
    {
        $customer = User::factory()
            ->unverified()
            ->create([
                'status' => CustomerStatus::Active,
            ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.index',
                ),
            )
            ->assertRedirect(
                route(
                    'verification.notice',
                ),
            );

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.show',
                    $order,
                ),
            )
            ->assertRedirect(
                route(
                    'verification.notice',
                ),
            );
    }

    public function test_suspended_customer_cannot_access_customer_orders(): void
    {
        $customer = User::factory()->create([
            'status' => CustomerStatus::from(
                'suspended',
            ),
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.show',
                    $order,
                ),
            );

        /*
         * customer.active is expected to terminate
         * access before the controller executes.
         */
        $response->assertRedirect(
            route('login'),
        );

        $this->assertGuest();
    }

    public function test_suspended_customer_cannot_cancel_owned_order(): void
    {
        $customer = User::factory()->create([
            'status' => CustomerStatus::from(
                'suspended',
            ),
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,

            'status' => OrderStatus::Pending,

            'fulfilment_status' => FulfilmentStatus::Unfulfilled,
        ]);

        $this
            ->actingAs($customer)
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Attempt while blocked.',
                ],
            )
            ->assertRedirect(
                route('login'),
            );

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );
    }

    public function test_anonymized_customer_cannot_access_historical_order(): void
    {
        $customer = User::factory()->create([
            'status' => CustomerStatus::from(
                'suspended',
            ),

            'anonymized_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.show',
                    $order,
                ),
            )
            ->assertRedirect(
                route('login'),
            );

        $this->assertGuest();

        /*
         * Historical order remains in storage even
         * though storefront access is denied.
         */
        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'user_id' => $customer->id,
            ],
        );
    }
}
