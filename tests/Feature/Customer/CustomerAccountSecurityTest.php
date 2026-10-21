<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customer_dashboard(): void
    {
        $response = $this->get(
            route(
                'customer.dashboard',
            ),
        );

        $response->assertRedirect(
            route(
                'login',
            ),
        );
    }

    public function test_unverified_customer_cannot_access_customer_dashboard(): void
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
                    'customer.dashboard',
                ),
            );

        $response->assertRedirect(
            route(
                'verification.notice',
            ),
        );
    }

    public function test_verified_customer_can_access_customer_dashboard(): void
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
                    ->component(
                        'Customer/Dashboard',
                    )
                    ->has(
                        'customer',
                    )
                    ->where(
                        'customer.id',
                        $user->id,
                    )
                    ->where(
                        'customer.name',
                        $user->name,
                    )
                    ->where(
                        'customer.email',
                        $user->email,
                    )
                    ->has(
                        'summary',
                    )
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

    public function test_legacy_dashboard_route_redirects_to_customer_account(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'dashboard',
                ),
            );

        $response->assertRedirect(
            route(
                'customer.dashboard',
            ),
        );
    }

    public function test_logout_uses_post_and_ends_customer_session(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'logout',
                ),
            );

        $this->assertGuest();

        $response->assertRedirect(
            route(
                'login',
            ),
        );
    }
}
