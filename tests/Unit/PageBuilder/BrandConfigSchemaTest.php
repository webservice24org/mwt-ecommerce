<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\BrandSection;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\BrandConfigSchema;
use PHPUnit\Framework\TestCase;

final class BrandConfigSchemaTest extends TestCase
{
    public function test_all_default_brand_configs_are_valid(): void
    {
        $definition =
            new BrandSection;

        foreach (
            [
                'logo_strip',
                'brand_cards',
                'logo_marquee',
                'spotlight_banner',
            ] as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertSame(
                $config,
                (
                    new BrandConfigSchema
                )->validate(
                    $config,
                ),
            );
        }
    }

    public function test_brand_configuration_is_normalized(): void
    {
        $config =
            (
                new BrandSection
            )->defaultConfig();

        $config['eyebrow'] =
            '  Trusted Partners  ';

        $config['heading'] =
            '  Shop by Brand  ';

        $config['description'] =
            '  Explore trusted brands.  ';

        $config[
            'background_color'
        ] =
            '#F8FAFC';

        $validated =
            (
                new BrandConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            'Trusted Partners',
            $validated['eyebrow'],
        );

        $this->assertSame(
            'Shop by Brand',
            $validated['heading'],
        );

        $this->assertSame(
            'Explore trusted brands.',
            $validated[
                'description'
            ],
        );

        $this->assertSame(
            '#f8fafc',
            $validated[
                'background_color'
            ],
        );
    }

    public function test_manual_source_requires_brand_selection(): void
    {
        $config =
            (
                new BrandSection
            )->defaultConfig();

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [],
        ];

        try {
            (
                new BrandConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected empty manual brand selection to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'source.brand_ids',
                $exception->errors(),
            );
        }
    }

    public function test_duplicate_manual_brand_ids_are_rejected(): void
    {
        $config =
            (
                new BrandSection
            )->defaultConfig();

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                2,
                2,
            ],
        ];

        try {
            (
                new BrandConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected duplicate brand IDs to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'source.brand_ids',
                $exception->errors(),
            );
        }
    }

    public function test_marquee_duration_must_be_within_supported_range(): void
    {
        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_marquee',
            );

        $config[
            'marquee_duration'
        ] =
            5;

        try {
            (
                new BrandConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected invalid marquee duration to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'marquee_duration',
                $exception->errors(),
            );
        }
    }

    public function test_button_label_and_url_must_be_paired(): void
    {
        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'spotlight_banner',
            );

        $config[
            'primary_button_label'
        ] =
            'Browse Brands';

        $config[
            'primary_button_url'
        ] =
            null;

        try {
            (
                new BrandConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected incomplete button configuration to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'primary_button_url',
                $exception->errors(),
            );
        }
    }

    public function test_unknown_brand_configuration_fields_are_rejected(): void
    {
        $config =
            (
                new BrandSection
            )->defaultConfig();

        $config[
            'unsafe_html'
        ] =
            '<script>alert(1)</script>';

        try {
            (
                new BrandConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected unsupported field to be rejected.',
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

    public function test_safe_internal_and_external_urls_are_accepted(): void
    {
        $config =
            $this->baseConfig();

        $config[
            'view_all_label'
        ] =
            'View Brands';

        $config[
            'view_all_url'
        ] =
            '/products?brand=nike';

        $config[
            'primary_button_label'
        ] =
            'Partner Site';

        $config[
            'primary_button_url'
        ] =
            'https://example.com/brands';

        $validated =
            (
                new BrandConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            '/products?brand=nike',
            $validated[
                'view_all_url'
            ],
        );

        $this->assertSame(
            'https://example.com/brands',
            $validated[
                'primary_button_url'
            ],
        );
    }

    public function test_unsafe_url_schemes_are_rejected(): void
    {
        foreach (
            [
                'javascript:alert(1)',
                'data:text/html,test',
                'file:///etc/passwd',
            ] as $unsafeUrl
        ) {
            $config =
                $this->baseConfig();

            $config[
                'primary_button_label'
            ] =
                'Unsafe';

            $config[
                'primary_button_url'
            ] =
                $unsafeUrl;

            try {
                (
                    new BrandConfigSchema
                )->validate(
                    $config,
                );

                $this->fail(
                    "Expected URL [{$unsafeUrl}] to be rejected.",
                );
            } catch (
                InvalidSectionConfiguration $exception
            ) {
                $this->assertArrayHasKey(
                    'primary_button_url',
                    $exception->errors(),
                );
            }
        }
    }

    public function test_protocol_relative_and_backslash_urls_are_rejected(): void
    {
        foreach (
            [
                '//evil.example',
                '/\\evil.example',
            ] as $unsafeUrl
        ) {
            $config =
                $this->baseConfig();

            $config[
                'view_all_label'
            ] =
                'View';

            $config[
                'view_all_url'
            ] =
                $unsafeUrl;

            try {
                (
                    new BrandConfigSchema
                )->validate(
                    $config,
                );

                $this->fail(
                    "Expected URL [{$unsafeUrl}] to be rejected.",
                );
            } catch (
                InvalidSectionConfiguration $exception
            ) {
                $this->assertArrayHasKey(
                    'view_all_url',
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

            'heading' => 'Shop by Brand',

            'description' => '',

            'source' => [
                'type' => 'all',
            ],

            'limit' => 12,

            'columns' => 4,

            'alignment' => 'center',

            'background_color' => '#ffffff',

            'text_theme' => 'dark',

            'show_name' => false,

            'show_description' => false,

            'show_product_count' => false,

            'view_all_label' => null,

            'view_all_url' => null,

            'primary_button_label' => null,

            'primary_button_url' => null,

            'secondary_button_label' => null,

            'secondary_button_url' => null,

            'marquee_duration' => 25,

            'pause_on_hover' => true,
        ];
    }
}
