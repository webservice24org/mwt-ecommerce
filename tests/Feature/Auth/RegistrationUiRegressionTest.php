<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

final class RegistrationUiRegressionTest extends TestCase
{
    public function test_registration_ui_tracks_server_identity_limits_and_named_route(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "post(route('register.store')",
            $source,
        );

        $this->assertStringContainsString(
            'maxLength={120}',
            $source,
        );

        $this->assertStringContainsString(
            'maxLength={254}',
            $source,
        );

        $this->assertStringContainsString(
            'autoComplete="name"',
            $source,
        );

        $this->assertStringContainsString(
            'autoComplete="email"',
            $source,
        );

        $this->assertStringContainsString(
            'inputMode="email"',
            $source,
        );

        $this->assertStringContainsString(
            'autoCapitalize="none"',
            $source,
        );
    }

    public function test_registration_ui_tracks_server_password_policy(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'data.password.length >= 10',
            $source,
        );

        $this->assertStringContainsString(
            '/[A-Z]/.test(data.password)',
            $source,
        );

        $this->assertStringContainsString(
            '/[a-z]/.test(data.password)',
            $source,
        );

        $this->assertStringContainsString(
            '/\d/.test(data.password)',
            $source,
        );

        $this->assertStringContainsString(
            '/[^A-Za-z0-9]/.test(data.password)',
            $source,
        );

        $this->assertStringContainsString(
            'minLength={10}',
            $source,
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                'maxLength={255}',
            ),
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                'autoComplete="new-password"',
            ),
        );
    }

    public function test_registration_ui_keeps_password_visibility_and_confirmation_feedback_accessible(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'aria-pressed={visible}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label={`${visible ? \'Hide\' : \'Show\'} ${label}`}',
            $source,
        );

        $this->assertStringContainsString(
            'id="password-requirements"',
            $source,
        );

        $this->assertStringContainsString(
            'id="password-confirmation-status"',
            $source,
        );

        $this->assertStringContainsString(
            'Passwords match.',
            $source,
        );

        $this->assertStringContainsString(
            'Passwords do not match yet.',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $source,
        );
    }

    public function test_registration_ui_keeps_validation_errors_and_processing_state_accessible(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'aria-invalid={',
            $source,
        );

        $this->assertStringContainsString(
            'role="alert"',
            $source,
        );

        $this->assertStringContainsString(
            'disabled={processing}',
            $source,
        );

        $this->assertStringContainsString(
            "processing ? 'Creating account...' : 'Create account'",
            $source,
        );

        $this->assertStringContainsString(
            'min-h-12',
            $source,
        );
    }

    public function test_registration_ui_explains_email_verification_before_protected_access(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'Email verification protects your account',
            $source,
        );

        $this->assertStringContainsString(
            'verify your email before accessing protected customer',
            $source,
        );

        $this->assertStringContainsString(
            "href={route('login')}",
            $source,
        );

        $this->assertStringContainsString(
            "href={route('home')}",
            $source,
        );
    }

    private function source(): string
    {
        $path = base_path(
            'resources/js/Pages/Auth/Register.tsx',
        );

        $contents = file_get_contents(
            $path,
        );

        if ($contents === false) {
            $this->fail(
                sprintf(
                    'Unable to read registration UI source file [%s].',
                    $path,
                ),
            );
        }

        return $contents;
    }
}
