<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerDashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_authenticated_customers_order_and_address_counts(): void
    {
        $user = User::factory()
            ->create();

        Order::factory()
            ->for($user)
            ->count(3)
            ->create();

        Address::factory()
            ->for($user)
            ->count(2)
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.dashboard',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Dashboard',
                    )
                    ->where(
                        'customer.id',
                        $user->id,
                    )
                    ->where(
                        'summary.orders',
                        3,
                    )
                    ->where(
                        'summary.addresses',
                        2,
                    ),
            );
    }

    public function test_dashboard_summary_does_not_include_another_customers_orders(): void
    {
        $user = User::factory()
            ->create();

        $otherUser = User::factory()
            ->create();

        Order::factory()
            ->for($user)
            ->count(2)
            ->create();

        Order::factory()
            ->for($otherUser)
            ->count(5)
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.dashboard',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'summary.orders',
                        2,
                    ),
            );
    }

    public function test_dashboard_summary_does_not_include_another_customers_addresses(): void
    {
        $user = User::factory()
            ->create();

        $otherUser = User::factory()
            ->create();

        Address::factory()
            ->for($user)
            ->create();

        Address::factory()
            ->for($otherUser)
            ->count(4)
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.dashboard',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'summary.addresses',
                        1,
                    ),
            );
    }

    public function test_customer_with_no_orders_or_addresses_sees_zero_counts(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.dashboard',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'summary.orders',
                        0,
                    )
                    ->where(
                        'summary.addresses',
                        0,
                    ),
            );
    }
}
