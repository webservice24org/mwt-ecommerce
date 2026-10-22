<?php

declare(strict_types=1);

namespace Tests\Feature\HeaderBuilder;

use Tests\TestCase;

final class HeaderAccountPopupRegressionTest extends TestCase
{
    public function test_account_popup_keeps_non_modal_dialog_semantics(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'aria-controls={accountMenuId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-expanded={accountOpen}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-haspopup="dialog"',
            $source,
        );

        $this->assertStringContainsString(
            'role="dialog"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-modal={false}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-labelledby={titleId}',
            $source,
        );

        $this->assertStringContainsString(
            'tabIndex={-1}',
            $source,
        );

        /*
         * The account popup contains rich content,
         * status messaging and CTA groups. It is
         * intentionally not modeled as an ARIA menu.
         */
        $this->assertStringNotContainsString(
            'role="menu"',
            $source,
        );

        $this->assertStringNotContainsString(
            'role="menuitem"',
            $source,
        );

        $this->assertStringNotContainsString(
            'aria-haspopup="menu"',
            $source,
        );
    }

    public function test_account_popup_keeps_keyboard_and_focus_recovery_contract(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "event.key !== 'Escape'",
            $source,
        );

        $this->assertStringContainsString(
            "event.key === 'ArrowDown'",
            $source,
        );

        $this->assertStringContainsString(
            'dialogRef.current?.focus()',
            $source,
        );

        $this->assertStringContainsString(
            'accountTriggerRef.current?.focus()',
            $source,
        );

        $this->assertStringContainsString(
            "document.addEventListener('pointerdown', handlePointerDown)",
            $source,
        );

        $this->assertStringContainsString(
            "document.removeEventListener('pointerdown', handlePointerDown)",
            $source,
        );

        $this->assertStringContainsString(
            'onBlur={(event) => {',
            $source,
        );

        $this->assertStringContainsString(
            'onAccountClose(false)',
            $source,
        );

        $this->assertStringContainsString(
            'onClose(true)',
            $source,
        );
    }

    public function test_guest_account_popup_keeps_safe_authentication_destinations(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "resolveGuestAccountUrl(config.actions.account.guest_login_url, '/login')",
            $source,
        );

        $this->assertStringContainsString(
            "config.actions.account.guest_register_url,\n        '/register',",
            $source,
        );

        $this->assertStringContainsString(
            "href={route('password.request')}",
            $source,
        );

        $this->assertStringContainsString(
            'Create account',
            $source,
        );
    }

    public function test_authenticated_account_popup_keeps_verification_and_customer_shortcuts(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'const emailVerified = user?.email_verified_at != null',
            $source,
        );

        $this->assertStringContainsString(
            "route('verification.notice')",
            $source,
        );

        $this->assertStringContainsString(
            "route('customer.dashboard')",
            $source,
        );

        $this->assertStringContainsString(
            "route('customer.orders.index')",
            $source,
        );

        $this->assertStringContainsString(
            "route('wishlist.index')",
            $source,
        );

        $this->assertStringContainsString(
            "route('profile.edit')",
            $source,
        );

        $this->assertStringContainsString(
            "route('logout')",
            $source,
        );

        $this->assertStringContainsString(
            "wishlistCount > 99 ? '99+' : wishlistCount",
            $source,
        );
    }

    public function test_mobile_account_experience_keeps_dedicated_responsive_boundary(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'function MobileAccountSection({',
            $source,
        );

        $this->assertStringContainsString(
            'aria-labelledby="mobile-account-heading"',
            $source,
        );

        $this->assertStringContainsString(
            'id="mobile-account-heading"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Mobile customer shortcuts"',
            $source,
        );

        $this->assertStringContainsString(
            'min-h-12',
            $source,
        );

        $this->assertStringContainsString(
            'min-h-16',
            $source,
        );
    }

    public function test_account_popup_does_not_reintroduce_hard_coded_customer_action_urls(): void
    {
        $source = $this->source();

        $this->assertStringNotContainsString(
            'href="/account"',
            $source,
        );

        $this->assertStringNotContainsString(
            'href="/profile"',
            $source,
        );

        $this->assertStringNotContainsString(
            "router.post('/logout')",
            $source,
        );
    }

    private function source(): string
    {
        $path = base_path(
            'resources/js/Components/Frontend/HeaderBuilder/MegaMenuHeader.tsx',
        );

        $contents = file_get_contents(
            $path,
        );

        if ($contents === false) {
            $this->fail(
                sprintf(
                    'Unable to read account popup regression source file [%s].',
                    $path,
                ),
            );
        }

        return $contents;
    }
}
