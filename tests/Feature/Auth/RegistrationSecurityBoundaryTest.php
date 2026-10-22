<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class RegistrationSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_registration_rotates_the_session_identifier(): void
    {
        Notification::fake();

        $this
            ->withSession([
                'registration_session_probe' => 'preserved',
            ])
            ->get(
                route('register'),
            )
            ->assertOk();

        $before = session()
            ->getId();

        $this
            ->post(
                route('register.store'),
                [
                    'name' => 'Session Customer',
                    'email' => 'session@example.com',
                    'password' => 'SecurePass1!',
                    'password_confirmation' => 'SecurePass1!',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('verification.notice'),
            );

        $after = session()
            ->getId();

        $this->assertNotSame(
            $before,
            $after,
            'Registration must rotate the session identifier after authentication.',
        );

        $this->assertSame(
            'preserved',
            session(
                'registration_session_probe',
            ),
        );

        $this->assertAuthenticated();

        $user = User::query()
            ->where(
                'email',
                'session@example.com',
            )
            ->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
        );
    }

    public function test_authenticated_customer_cannot_register_a_second_account_in_the_same_session(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route('register.store'),
                [
                    'name' => 'Second Identity',
                    'email' => 'second@example.com',
                    'password' => 'SecurePass1!',
                    'password_confirmation' => 'SecurePass1!',
                ],
            )
            ->assertRedirect();

        $this->assertAuthenticatedAs(
            $user,
        );

        $this->assertDatabaseCount(
            'users',
            1,
        );

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'second@example.com',
            ],
        );

        Notification::assertNothingSent();
    }

    public function test_invalid_registration_does_not_create_or_authenticate_a_customer(): void
    {
        Notification::fake();

        $this
            ->post(
                route('register.store'),
                [
                    'name' => 'Invalid Customer',
                    'email' => 'invalid@example.com',
                    'password' => 'weak',
                    'password_confirmation' => 'weak',
                ],
            )
            ->assertSessionHasErrors([
                'password',
            ]);

        $this->assertGuest();

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'invalid@example.com',
            ],
        );

        Notification::assertNothingSent();
    }

    public function test_duplicate_registration_cannot_change_existing_customer_credentials(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => 'OriginalPass1!',
        ]);

        $this
            ->post(
                route('register.store'),
                [
                    'name' => 'Replacement Identity',
                    'email' => ' EXISTING@EXAMPLE.COM ',
                    'password' => 'ReplacementPass1!',
                    'password_confirmation' => 'ReplacementPass1!',
                ],
            )
            ->assertSessionHasErrors([
                'email',
            ]);

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'OriginalPass1!',
                $user->password,
            ),
        );

        $this->assertFalse(
            Hash::check(
                'ReplacementPass1!',
                $user->password,
            ),
        );

        $this->assertSame(
            1,
            User::query()
                ->where(
                    'email',
                    'existing@example.com',
                )
                ->count(),
        );

        $this->assertGuest();

        Notification::assertNothingSent();
    }

    public function test_registration_route_keeps_guest_and_rate_limit_boundaries(): void
    {
        $route = $this->route(
            'register.store',
        );

        $middleware = $route
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

    public function test_logout_requires_authentication_but_does_not_require_active_customer_status(): void
    {
        $route = $this->route(
            'logout',
        );

        $middleware = $route
            ->gatherMiddleware();

        $this->assertContains(
            'auth',
            $middleware,
        );

        $this->assertNotContains(
            'customer.active',
            $middleware,
        );
    }

    public function test_verification_and_sensitive_customer_auth_routes_still_require_active_status(): void
    {
        foreach ([
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.confirm',
            'password.update',
        ] as $routeName) {
            $middleware = $this
                ->route(
                    $routeName,
                )
                ->gatherMiddleware();

            $this->assertContains(
                'auth',
                $middleware,
                sprintf(
                    'Route [%s] must remain authenticated.',
                    $routeName,
                ),
            );

            $this->assertContains(
                'customer.active',
                $middleware,
                sprintf(
                    'Route [%s] must remain protected by customer.active.',
                    $routeName,
                ),
            );
        }
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
