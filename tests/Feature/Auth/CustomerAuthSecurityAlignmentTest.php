<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class CustomerAuthSecurityAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_request_normalizes_email_before_authentication(): void
    {
        $request = LoginRequest::create(
            '/login',
            'POST',
            [
                'email' => '  CUSTOMER@EXAMPLE.COM  ',
                'password' => 'Secret123!',
            ],
        );

        $request->setContainer(
            app(),
        );

        $request->validateResolved();

        $this->assertSame(
            'customer@example.com',
            $request->input(
                'email',
            ),
        );
    }

    public function test_successful_login_rotates_session_and_uses_customer_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'SecurePass1!',
        ]);

        $this
            ->withSession([
                'login_session_probe' => 'preserved',
            ])
            ->get(
                route('login'),
            )
            ->assertOk();

        $before = session()
            ->getId();

        $this
            ->post(
                route('login.store'),
                [
                    'email' => ' CUSTOMER@EXAMPLE.COM ',
                    'password' => 'SecurePass1!',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'customer.dashboard',
                    absolute: false,
                ),
            );

        $this->assertAuthenticatedAs(
            $user,
        );

        $this->assertNotSame(
            $before,
            session()->getId(),
        );

        $this->assertSame(
            'preserved',
            session(
                'login_session_probe',
            ),
        );
    }

    public function test_unknown_password_reset_email_receives_generic_response_without_notification(): void
    {
        Notification::fake();

        $this
            ->from(
                route('password.request'),
            )
            ->post(
                route('password.email'),
                [
                    'email' => 'unknown@example.com',
                ],
            )
            ->assertRedirect(
                route('password.request'),
            )
            ->assertSessionHas(
                'status',
                'If an eligible account exists for that email address, a password reset link has been sent.',
            )
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_active_customer_can_receive_password_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $this
            ->from(
                route('password.request'),
            )
            ->post(
                route('password.email'),
                [
                    'email' => ' RESET@EXAMPLE.COM ',
                ],
            )
            ->assertRedirect(
                route('password.request'),
            )
            ->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
        );
    }

    public function test_reset_password_requires_same_strong_policy_as_registration(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'policy@example.com',
            'password' => 'OriginalSecure1!',
        ]);

        $token = $this->passwordResetToken(
            $user,
        );

        $this
            ->post(
                route('password.store'),
                [
                    'token' => $token,
                    'email' => $user->email,
                    'password' => 'password',
                    'password_confirmation' => 'password',
                ],
            )
            ->assertSessionHasErrors([
                'password',
            ]);

        $freshUser = $user->fresh();

        $this->assertNotNull(
            $freshUser,
        );

        $this->assertTrue(
            Hash::check(
                'OriginalSecure1!',
                $freshUser->password,
            ),
        );

        $this->assertFalse(
            Hash::check(
                'password',
                $freshUser->password,
            ),
        );
    }

    public function test_valid_password_reset_rotates_remember_token_and_dispatches_event(): void
    {
        $user = User::factory()->create([
            'email' => 'valid-reset@example.com',
            'password' => 'OldSecure1!',
            'remember_token' => 'old-remember-token',
        ]);

        $token = $this->passwordResetToken(
            $user,
        );

        Event::fake();

        $this
            ->post(
                route('password.store'),
                [
                    'token' => $token,
                    'email' => ' VALID-RESET@EXAMPLE.COM ',
                    'password' => 'NewSecure2!',
                    'password_confirmation' => 'NewSecure2!',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('login'),
            );

        $freshUser = $user->fresh();

        $this->assertNotNull(
            $freshUser,
        );

        $this->assertTrue(
            Hash::check(
                'NewSecure2!',
                $freshUser->password,
            ),
        );

        $this->assertNotSame(
            'old-remember-token',
            $freshUser->remember_token,
        );

        Event::assertDispatched(
            PasswordReset::class,
        );
    }

    public function test_password_recovery_routes_keep_abuse_throttles(): void
    {
        foreach ([
            'password.email',
            'password.store',
        ] as $routeName) {
            $middleware = $this
                ->route(
                    $routeName,
                )
                ->gatherMiddleware();

            $this->assertContains(
                'guest',
                $middleware,
            );

            $this->assertContains(
                'throttle:5,1',
                $middleware,
            );
        }
    }

    private function passwordResetToken(
        User $user,
    ): string {
        $broker = Password::broker();

        if (! $broker instanceof PasswordBroker) {
            $this->fail(
                sprintf(
                    'Expected password broker [%s], got [%s].',
                    PasswordBroker::class,
                    get_debug_type(
                        $broker,
                    ),
                ),
            );
        }

        return $broker->createToken(
            $user,
        );
    }

    private function route(
        string $name,
    ): Route {
        $route = app(
            'router',
        )
            ->getRoutes()
            ->getByName(
                $name,
            );

        if (! $route instanceof Route) {
            $this->fail(
                sprintf(
                    'Expected route [%s] to be registered.',
                    $name,
                ),
            );
        }

        return $route;
    }
}
