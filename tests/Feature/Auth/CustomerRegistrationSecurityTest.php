<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerRegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        $response = $this->post(
            route('register'),
            [
                'name' => 'Customer User',
                'email' => 'customer@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
        );

        $response->assertRedirect(
            route('customer.dashboard'),
        );

        $user = User::query()
            ->where(
                'email',
                'customer@example.com',
            )
            ->firstOrFail();

        $this->assertSame(
            CustomerStatus::Active,
            $user->status,
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_cannot_choose_account_status_during_registration(): void
    {
        $this->post(
            route('register'),
            [
                'name' => 'Customer User',
                'email' => 'customer@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'status' => CustomerStatus::Suspended->value,
            ],
        );

        $user = User::query()
            ->where(
                'email',
                'customer@example.com',
            )
            ->firstOrFail();

        $this->assertSame(
            CustomerStatus::Active,
            $user->status,
        );
    }
}
