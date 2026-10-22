<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_customer_can_open_the_email_verification_screen(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route('verification.notice'),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component(
                        'Auth/VerifyEmail',
                    )
                    ->where(
                        'email',
                        $user->email,
                    )
                    ->where(
                        'status',
                        null,
                    ),
            );
    }

    public function test_verified_customer_is_redirected_away_from_verification_screen(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route('verification.notice'),
            )
            ->assertRedirect(
                route('customer.dashboard'),
            );
    }

    public function test_email_can_be_verified_and_customer_is_sent_to_customer_dashboard(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(
                60,
            ),
            [
                'id' => $user->id,
                'hash' => sha1(
                    $user->email,
                ),
            ],
        );

        $this
            ->actingAs(
                $user,
            )
            ->get(
                $verificationUrl,
            )
            ->assertRedirect(
                route(
                    'customer.dashboard',
                    [
                        'verified' => 1,
                    ],
                ),
            );

        Event::assertDispatched(
            Verified::class,
        );

        $this->assertTrue(
            $user
                ->fresh()
                ?->hasVerifiedEmail()
            ?? false,
        );
    }

    public function test_already_verified_customer_can_safely_reuse_a_valid_verification_link(): void
    {
        $user = User::factory()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(
                60,
            ),
            [
                'id' => $user->id,
                'hash' => sha1(
                    $user->email,
                ),
            ],
        );

        $this
            ->actingAs(
                $user,
            )
            ->get(
                $verificationUrl,
            )
            ->assertRedirect(
                route(
                    'customer.dashboard',
                    [
                        'verified' => 1,
                    ],
                ),
            );

        Event::assertNotDispatched(
            Verified::class,
        );
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(
                60,
            ),
            [
                'id' => $user->id,
                'hash' => sha1(
                    'wrong-email@example.com',
                ),
            ],
        );

        $this
            ->actingAs(
                $user,
            )
            ->get(
                $verificationUrl,
            )
            ->assertForbidden();

        $this->assertFalse(
            $user
                ->fresh()
                ?->hasVerifiedEmail()
            ?? true,
        );
    }

    public function test_unverified_customer_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()
            ->unverified()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->from(
                route('verification.notice'),
            )
            ->post(
                route('verification.send'),
            )
            ->assertRedirect(
                route('verification.notice'),
            )
            ->assertSessionHas(
                'status',
                'verification-link-sent',
            );

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
        );
    }

    public function test_verified_customer_does_not_receive_another_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route('verification.send'),
            )
            ->assertRedirect(
                route('customer.dashboard'),
            );

        Notification::assertNothingSent();
    }

    public function test_verification_email_resend_endpoint_is_throttled(): void
    {
        Notification::fake();

        $user = User::factory()
            ->unverified()
            ->create();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this
                ->actingAs(
                    $user,
                )
                ->post(
                    route('verification.send'),
                )
                ->assertRedirect();
        }

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route('verification.send'),
            )
            ->assertStatus(
                429,
            );
    }
}
