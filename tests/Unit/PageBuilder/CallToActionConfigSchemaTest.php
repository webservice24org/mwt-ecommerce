<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\CallToActionConfigSchema;
use PHPUnit\Framework\TestCase;

final class CallToActionConfigSchemaTest extends TestCase
{
    public function test_valid_configuration_is_normalized(): void
    {
        $config = (
            new CallToActionConfigSchema
        )->validate([
            'eyebrow' => '  Ready to start?  ',
            'heading' => '  Let us build together  ',
            'description' => '  Contact our team today.  ',
            'background_type' => 'image',
            'background_color' => '  #0F172A  ',
            'background_image' => '  /storage/page-builder/cta.webp  ',
            'background_overlay' => 72,
            'text_theme' => 'light',
            'phone_label' => '  Call Us  ',
            'phone_number' => '  +880 1700-000000  ',
            'email_label' => '  Email Us  ',
            'email' => '  hello@example.com  ',
            'whatsapp_label' => '  WhatsApp  ',
            'whatsapp_number' => '  +880 1700-000000  ',
            'whatsapp_message' => '  Hello, I would like to discuss a project.  ',
            'newsletter_placeholder' => '  Enter your email address  ',
            'newsletter_button_label' => '  Subscribe  ',
            'newsletter_note' => '  No spam, unsubscribe anytime.  ',
        ]);

        $this->assertSame(
            [
                'eyebrow' => 'Ready to start?',
                'heading' => 'Let us build together',
                'description' => 'Contact our team today.',
                'background_type' => 'image',
                'background_color' => '#0f172a',
                'background_image' => '/storage/page-builder/cta.webp',
                'background_overlay' => 72,
                'text_theme' => 'light',
                'phone_label' => 'Call Us',
                'phone_number' => '+880 1700-000000',
                'email_label' => 'Email Us',
                'email' => 'hello@example.com',
                'whatsapp_label' => 'WhatsApp',
                'whatsapp_number' => '+880 1700-000000',
                'whatsapp_message' => 'Hello, I would like to discuss a project.',
                'newsletter_placeholder' => 'Enter your email address',
                'newsletter_button_label' => 'Subscribe',
                'newsletter_note' => 'No spam, unsubscribe anytime.',
            ],
            $config,
        );
    }

    public function test_optional_fields_normalize_to_safe_empty_values(): void
    {
        $config = (
            new CallToActionConfigSchema
        )->validate([
            'eyebrow' => '',
            'heading' => 'CTA Heading',
            'description' => null,
            'background_type' => 'color',
            'background_color' => '#ffffff',
            'background_image' => '',
            'background_overlay' => 0,
            'text_theme' => 'dark',
            'phone_label' => null,
            'phone_number' => null,
            'email_label' => null,
            'email' => null,
            'whatsapp_label' => null,
            'whatsapp_number' => null,
            'whatsapp_message' => null,
            'newsletter_placeholder' => null,
            'newsletter_button_label' => null,
            'newsletter_note' => null,
        ]);

        $this->assertSame(
            null,
            $config['eyebrow'],
        );

        $this->assertSame(
            '',
            $config['description'],
        );

        $this->assertSame(
            null,
            $config['background_image'],
        );
    }

    public function test_background_can_be_color_or_image(): void
    {
        $schema =
            new CallToActionConfigSchema;

        foreach (
            [
                [
                    'background_type' => 'color',
                    'background_image' => null,
                ],
                [
                    'background_type' => 'image',
                    'background_image' => '/storage/page-builder/cta.webp',
                ],
            ] as $background
        ) {
            $config = $this->baseConfig();

            $config['background_type'] =
                $background['background_type'];

            $config['background_image'] =
                $background['background_image'];

            $validated =
                $schema->validate(
                    $config,
                );

            $this->assertSame(
                $background['background_type'],
                $validated['background_type'],
            );
        }
    }

    public function test_image_background_requires_an_image(): void
    {
        $config = $this->baseConfig();
        $config['background_type'] = 'image';
        $config['background_image'] = null;

        try {
            (new CallToActionConfigSchema)->validate($config);

            $this->fail(
                'Expected image background without an image to be rejected.',
            );
        } catch (InvalidSectionConfiguration $exception) {
            $this->assertArrayHasKey(
                'background_image',
                $exception->errors(),
            );
        }
    }

    public function test_invalid_background_configuration_is_rejected(): void
    {
        $config =
            $this->baseConfig();

        $config['background_type'] =
            'video';

        $config['background_color'] =
            'red';

        $config['background_overlay'] =
            101;

        $config['text_theme'] =
            'neon';

        try {
            (
                new CallToActionConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected invalid background configuration to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $errors =
                $exception->errors();

            $this->assertArrayHasKey(
                'background_type',
                $errors,
            );

            $this->assertArrayHasKey(
                'background_color',
                $errors,
            );

            $this->assertArrayHasKey(
                'background_overlay',
                $errors,
            );

            $this->assertArrayHasKey(
                'text_theme',
                $errors,
            );
        }
    }

    public function test_invalid_contact_values_are_rejected(): void
    {
        $config =
            $this->baseConfig();

        $config['phone_number'] =
            'phone<script>';

        $config['email'] =
            'not-an-email';

        $config['whatsapp_number'] =
            'abc';

        try {
            (
                new CallToActionConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected invalid contact values to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $errors =
                $exception->errors();

            $this->assertArrayHasKey(
                'phone_number',
                $errors,
            );

            $this->assertArrayHasKey(
                'email',
                $errors,
            );

            $this->assertArrayHasKey(
                'whatsapp_number',
                $errors,
            );
        }
    }

    public function test_unknown_configuration_field_is_rejected(): void
    {
        $config =
            $this->baseConfig();

        $config['unsafe_html'] =
            '<script>alert(1)</script>';

        try {
            (
                new CallToActionConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected unsupported CTA configuration field to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'unsafe_html',
                $exception->errors(),
            );
        }
    }

    public function test_background_image_accepts_safe_relative_and_http_urls(): void
    {
        foreach (
            [
                '/storage/page-builder/cta.webp',
                'https://cdn.example.com/cta.webp',
                'http://cdn.example.com/cta.webp',
            ] as $image
        ) {
            $config =
                $this->baseConfig();

            $config['background_type'] =
                'image';

            $config['background_image'] =
                $image;

            $validated = (
                new CallToActionConfigSchema
            )->validate(
                $config,
            );

            $this->assertSame(
                $image,
                $validated[
                    'background_image'
                ],
            );
        }
    }

    public function test_unsafe_background_image_references_are_rejected(): void
    {
        foreach (
            [
                'javascript:alert(1)',
                'data:image/svg+xml,<svg></svg>',
                'file:///etc/passwd',
                'ftp://example.com/image.jpg',
                '//evil.example/image.jpg',
            ] as $image
        ) {
            $config =
                $this->baseConfig();

            $config['background_type'] =
                'image';

            $config['background_image'] =
                $image;

            try {
                (
                    new CallToActionConfigSchema
                )->validate(
                    $config,
                );

                $this->fail(
                    "Expected background image [{$image}] to be rejected.",
                );
            } catch (
                InvalidSectionConfiguration $exception
            ) {
                $this->assertArrayHasKey(
                    'background_image',
                    $exception->errors(),
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function baseConfig(): array
    {
        return [
            'eyebrow' => null,
            'heading' => 'CTA Heading',
            'description' => 'CTA description.',
            'background_type' => 'color',
            'background_color' => '#0f172a',
            'background_image' => null,
            'background_overlay' => 70,
            'text_theme' => 'light',
            'phone_label' => 'Call Us',
            'phone_number' => null,
            'email_label' => 'Email Us',
            'email' => null,
            'whatsapp_label' => 'WhatsApp',
            'whatsapp_number' => null,
            'whatsapp_message' => null,
            'newsletter_placeholder' => null,
            'newsletter_button_label' => null,
            'newsletter_note' => null,
        ];
    }
}
