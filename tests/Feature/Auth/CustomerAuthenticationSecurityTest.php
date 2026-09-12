<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerAuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_customer_can_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'status' => CustomerStatus::Active,
        ]);

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password123',
            ],
        );

        $response->assertRedirect(
            route('customer.dashboard'),
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_customer_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'status' => CustomerStatus::Suspended,
        ]);

        $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password123',
            ],
        );

        $this->assertGuest();
    }

    public function test_disabled_customer_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'status' => CustomerStatus::Disabled,
        ]);

        $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password123',
            ],
        );

        $this->assertGuest();
    }

    public function test_guest_cannot_access_customer_dashboard(): void
    {
        $response = $this->get(
            route('customer.dashboard'),
        );

        $response->assertRedirect(
            route('login'),
        );
    }

    public function test_unverified_customer_cannot_access_customer_dashboard(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route('customer.dashboard'),
            );

        $response->assertRedirect(
            route('verification.notice'),
        );
    }

    public function test_suspended_authenticated_customer_is_logged_out(): void
    {
        $user = User::factory()->create([
            'status' => CustomerStatus::Suspended,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('customer.dashboard'),
            );

        $response->assertRedirect(
            route('login'),
        );

        $this->assertGuest();
    }
}
