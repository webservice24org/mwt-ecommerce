<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this
            ->get(
                route('register'),
            )
            ->assertOk();
    }

    public function test_new_customers_register_with_normalized_identity_and_are_sent_to_verification(): void
    {
        Notification::fake();

        $response = $this->post(
            route('register.store'),
            [
                'name' => '  Jane Customer  ',
                'email' => '  JANE@EXAMPLE.COM  ',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ],
        );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('verification.notice'),
            );

        $this->assertAuthenticated();

        $user = User::query()
            ->where(
                'email',
                'jane@example.com',
            )
            ->firstOrFail();

        $this->assertSame(
            'Jane Customer',
            $user->name,
        );

        $this->assertSame(
            CustomerStatus::Active,
            $user->status,
        );

        $this->assertNull(
            $user->email_verified_at,
        );

        $this->assertTrue(
            Hash::check(
                'SecurePass1!',
                $user->password,
            ),
        );

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
        );
    }

    public function test_registration_rejects_duplicate_normalized_email_addresses(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this
            ->post(
                route('register.store'),
                [
                    'name' => 'Another Customer',
                    'email' => ' EXISTING@EXAMPLE.COM ',
                    'password' => 'SecurePass1!',
                    'password_confirmation' => 'SecurePass1!',
                ],
            )
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'users',
            1,
        );
    }

    public function test_registration_rejects_weak_passwords(): void
    {
        $this
            ->post(
                route('register.store'),
                [
                    'name' => 'Weak Password Customer',
                    'email' => 'weak@example.com',
                    'password' => 'password',
                    'password_confirmation' => 'password',
                ],
            )
            ->assertSessionHasErrors([
                'password',
            ]);

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'weak@example.com',
            ],
        );
    }

    public function test_registration_cannot_mass_assign_customer_status(): void
    {
        Notification::fake();

        $this
            ->post(
                route('register.store'),
                [
                    'name' => 'Safe Customer',
                    'email' => 'safe@example.com',
                    'password' => 'SecurePass1!',
                    'password_confirmation' => 'SecurePass1!',
                    'status' => 'suspended',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('verification.notice'),
            );

        $user = User::query()
            ->where(
                'email',
                'safe@example.com',
            )
            ->firstOrFail();

        $this->assertSame(
            CustomerStatus::Active,
            $user->status,
        );
    }

    public function test_registration_endpoint_is_throttled(): void
    {
        $payload = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this
                ->post(
                    route('register.store'),
                    $payload,
                )
                ->assertSessionHasErrors();
        }

        $this
            ->post(
                route('register.store'),
                $payload,
            )
            ->assertStatus(
                429,
            );
    }
}
