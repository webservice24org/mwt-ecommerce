<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerOrderListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_order_history(): void
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

    public function test_unverified_customer_cannot_access_order_history(): void
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

    public function test_customer_only_sees_own_orders(): void
    {
        $user = User::factory()
            ->create();

        $otherUser = User::factory()
            ->create();

        $ownOrder = Order::factory()
            ->for($user)
            ->create([
                'order_number' => 'ORD-OWN-001',

                'status' => OrderStatus::Pending,

                'grand_total' => 25000,

                'placed_at' => now(),
            ]);

        Order::factory()
            ->for($otherUser)
            ->create([
                'order_number' => 'ORD-OTHER-001',

                'placed_at' => now(),
            ]);

        $response = $this
            ->actingAs(
                $user,
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
                    )
                    ->where(
                        'orders.data.0.grand_total',
                        25000,
                    ),
            );
    }

    public function test_orders_are_returned_newest_first(): void
    {
        $user = User::factory()
            ->create();

        Order::factory()
            ->for($user)
            ->create([
                'order_number' => 'ORD-OLDER',

                'placed_at' => now()->subDay(),
            ]);

        Order::factory()
            ->for($user)
            ->create([
                'order_number' => 'ORD-NEWEST',

                'placed_at' => now(),
            ]);

        $response = $this
            ->actingAs(
                $user,
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
                    ->where(
                        'orders.data.0.order_number',
                        'ORD-NEWEST',
                    )
                    ->where(
                        'orders.data.1.order_number',
                        'ORD-OLDER',
                    ),
            );
    }

    public function test_order_history_is_paginated(): void
    {
        $user = User::factory()
            ->create();

        Order::factory()
            ->for($user)
            ->count(11)
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

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->has(
                        'orders.data',
                        10,
                    )
                    ->where(
                        'orders.total',
                        11,
                    )
                    ->where(
                        'orders.current_page',
                        1,
                    )
                    ->where(
                        'orders.last_page',
                        2,
                    ),
            );
    }

    public function test_empty_customer_order_history_returns_empty_paginated_collection(): void
    {
        $user = User::factory()
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

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->has(
                        'orders.data',
                        0,
                    )
                    ->where(
                        'orders.total',
                        0,
                    ),
            );
    }
}
