<?php

declare(strict_types=1);

namespace Tests\Feature\FooterBuilder;

use Tests\TestCase;

final class FooterAccessibilityRegressionTest extends TestCase
{
    public function test_shared_bottom_footer_preserves_accessible_social_navigation(): void
    {
        $source =
            $this->source(
                'resources/js/Components/FooterBuilder/SharedBottomFooter.tsx',
            );

        $this->assertStringContainsString(
            'aria-label="Footer social links"',
            $source,
        );

        $this->assertStringContainsString(
            '<ul className={className}>{children}</ul>',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label={`Follow us on ${label}`}',
            $source,
        );

        $this->assertStringContainsString(
            'focus-visible:outline-none',
            $source,
        );

        /*
         * The animation must disappear for users
         * requesting reduced motion while leaving
         * the persistent status dot visible.
         */
        $this->assertStringContainsString(
            'motion-safe:animate-ping',
            $source,
        );

        /*
         * The visible developer prefix already
         * precedes the link, so the link itself
         * should not repeat the complete phrase
         * through aria-label.
         */
        $this->assertStringNotContainsString(
            'aria-label={`${prefix} ${name}`}',
            $source,
        );

        /*
         * SharedBottomFooter is intentionally only
         * a footer fragment. The template renderer
         * owns the actual <footer> landmark.
         */
        $this->assertStringNotContainsString(
            '<footer',
            $source,
        );
    }

    public function test_luxe_footer_preserves_accessible_newsletter_and_collection_semantics(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Frontend/FooterBuilder/LuxeNewsletterFooter.tsx',
            );

        $this->assertSame(
            1,
            substr_count(
                $source,
                '<footer',
            ),
        );

        $this->assertStringContainsString(
            'aria-label="Store footer"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Store benefits"',
            $source,
        );

        /*
         * Value propositions are a real collection,
         * not only a visual grid.
         */
        $this->assertStringContainsString(
            '<ul className="mx-auto grid',
            $source,
        );

        /*
         * Payment methods are exposed as a named
         * semantic section and list.
         */
        $this->assertStringContainsString(
            'aria-labelledby="footer-payment-methods-heading"',
            $source,
        );

        $this->assertStringContainsString(
            'id="footer-payment-methods-heading"',
            $source,
        );

        /*
         * Newsletter input security/accessibility
         * must remain aligned with the backend
         * 254-character email limit.
         */
        $this->assertStringContainsString(
            'maxLength={254}',
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
            'aria-invalid={',
            $source,
        );

        $this->assertStringContainsString(
            "errorBag: 'newsletterSubscription'",
            $source,
        );

        /*
         * Invalid submissions return keyboard
         * focus to the email field.
         */
        $this->assertStringContainsString(
            'inputRef.current?.focus()',
            $source,
        );

        /*
         * Error and success feedback must retain
         * explicit assistive-technology semantics.
         */
        $this->assertStringContainsString(
            'role="alert"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-atomic="true"',
            $source,
        );

        $this->assertStringContainsString(
            'recentlySuccessful',
            $source,
        );

        $this->assertStringContainsString(
            'role="status"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $source,
        );

        $this->assertStringContainsString(
            'focus-visible:ring',
            $source,
        );
    }

    public function test_minimal_footer_keeps_localization_informational_and_non_interactive(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Frontend/FooterBuilder/MinimalLocalizationFooter.tsx',
            );

        $this->assertSame(
            1,
            substr_count(
                $source,
                '<footer',
            ),
        );

        $this->assertStringContainsString(
            'aria-label="Store footer"',
            $source,
        );

        /*
         * v1 localization is informational only.
         * It must not masquerade as a functional
         * language/currency selector.
         */
        $this->assertStringContainsString(
            'Available regional options are shown below.',
            $source,
        );

        $this->assertStringContainsString(
            'Changing the',
            $source,
        );

        $this->assertStringContainsString(
            'application language or currency is not enabled by the',
            $source,
        );

        /*
         * Regional options remain semantic lists,
         * but they are not fake form controls.
         */
        $this->assertStringContainsString(
            '<ul className="mt-2 flex',
            $source,
        );

        $this->assertStringNotContainsString(
            '<select',
            $source,
        );

        $this->assertStringNotContainsString(
            'role="combobox"',
            $source,
        );

        $this->assertStringContainsString(
            'focus-visible:ring',
            $source,
        );
    }

    public function test_marketplace_footer_preserves_navigation_and_trust_semantics(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Frontend/FooterBuilder/MarketplaceTrustFooter.tsx',
            );

        $this->assertSame(
            1,
            substr_count(
                $source,
                '<footer',
            ),
        );

        $this->assertStringContainsString(
            'aria-label="Store footer"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Current promotion"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Popular searches and categories"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-labelledby="footer-certifications-heading"',
            $source,
        );

        $this->assertStringContainsString(
            'id="footer-certifications-heading"',
            $source,
        );

        /*
         * Promotion codes are data/code values,
         * rather than ordinary decorative text.
         */
        $this->assertStringContainsString(
            '<code className=',
            $source,
        );

        $this->assertStringContainsString(
            'focus-visible:ring',
            $source,
        );

        /*
         * The decorative CTA arrow must stay out
         * of the accessible name.
         */
        $this->assertStringContainsString(
            '<span aria-hidden="true" className="ml-1',
            $source,
        );
    }

    public function test_storefront_footer_dispatcher_keeps_one_template_boundary(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Frontend/FooterBuilder/StorefrontFooter.tsx',
            );

        $this->assertStringContainsString(
            "case 'luxe_newsletter':",
            $source,
        );

        $this->assertStringContainsString(
            "case 'minimal_localized':",
            $source,
        );

        $this->assertStringContainsString(
            "case 'marketplace_trust':",
            $source,
        );

        $this->assertStringContainsString(
            'if (!footer)',
            $source,
        );

        /*
         * The dispatcher must not introduce its
         * own footer landmark around a template,
         * otherwise nested landmarks are possible.
         */
        $this->assertStringNotContainsString(
            '<footer',
            $source,
        );
    }

    private function source(
        string $relativePath,
    ): string {
        $path =
            base_path(
                $relativePath,
            );

        $contents =
            file_get_contents(
                $path,
            );

        if (
            $contents ===
            false
        ) {
            $this->fail(
                sprintf(
                    'Unable to read regression source file [%s].',
                    $relativePath,
                ),
            );
        }

        return $contents;
    }
}
