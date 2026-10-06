<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\FeaturesBenefitsSection;
use App\Domain\PageBuilder\Sections\Schemas\FeaturesBenefitsConfigSchema;
use PHPUnit\Framework\TestCase;

final class FeaturesBenefitsConfigSchemaTest extends TestCase
{
    public function test_valid_configuration_is_normalized(): void
    {
        $config = (
            new FeaturesBenefitsConfigSchema
        )->validate([
            'eyebrow' => '  Why choose us  ',

            'heading' => '  Better shopping  ',

            'description' => '  Useful benefits for every customer.  ',

            'items' => [
                [
                    'title' => '  Fast Delivery  ',

                    'description' => '  Reliable delivery service.  ',

                    'icon' => '  truck  ',

                    'image' => null,

                    'image_alt' => null,

                    'link_label' => '  Learn more  ',

                    'link_url' => '  /delivery  ',
                ],
            ],

            'columns' => 3,

            'alignment' => 'center',

            'background_color' => '  #FFFFFF  ',

            'text_theme' => 'dark',
        ]);

        $this->assertSame(
            'Why choose us',
            $config['eyebrow'],
        );

        $this->assertSame(
            'Better shopping',
            $config['heading'],
        );

        $this->assertSame(
            'Useful benefits for every customer.',
            $config['description'],
        );

        $this->assertSame(
            '#ffffff',
            $config['background_color'],
        );

        $this->assertSame(
            'Fast Delivery',
            $config['items'][0][
                'title'
            ],
        );

        $this->assertSame(
            'truck',
            $config['items'][0][
                'icon'
            ],
        );

        $this->assertSame(
            '/delivery',
            $config['items'][0][
                'link_url'
            ],
        );
    }

    public function test_at_least_one_item_is_required(): void
    {
        $config =
            $this->baseConfig();

        $config['items'] = [];

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected empty features list to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'items',
                $exception->errors(),
            );
        }
    }

    public function test_invalid_columns_and_alignment_are_rejected(): void
    {
        $config =
            $this->baseConfig();

        $config['columns'] = 5;

        $config['alignment'] =
            'right';

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected invalid layout configuration to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $errors =
                $exception->errors();

            $this->assertArrayHasKey(
                'columns',
                $errors,
            );

            $this->assertArrayHasKey(
                'alignment',
                $errors,
            );
        }
    }

    public function test_item_requires_a_title(): void
    {
        $config =
            $this->baseConfig();

        $config['items'][0][
            'title'
        ] = '';

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected feature item without a title to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'items.0.title',
                $exception->errors(),
            );
        }
    }

    public function test_link_label_and_url_must_be_supplied_together(): void
    {
        $config =
            $this->baseConfig();

        $config['items'][0][
            'link_label'
        ] = 'Learn more';

        $config['items'][0][
            'link_url'
        ] = null;

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected incomplete feature link to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'items.0.link_url',
                $exception->errors(),
            );
        }
    }

    public function test_unknown_configuration_fields_are_rejected(): void
    {
        $config =
            $this->baseConfig();

        $config['unsafe_html'] =
            '<script>alert(1)</script>';

        $config['items'][0][
            'unsafe_html'
        ] =
            '<script>alert(1)</script>';

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected unsupported configuration fields to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $errors =
                $exception->errors();

            $this->assertArrayHasKey(
                'unsafe_html',
                $errors,
            );

            $this->assertArrayHasKey(
                'items.0.unsafe_html',
                $errors,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function baseConfig(): array
    {
        return [
            'eyebrow' => null,

            'heading' => 'Why shop with us',

            'description' => 'Useful customer benefits.',

            'items' => [
                [
                    'title' => 'Fast Delivery',

                    'description' => 'Reliable delivery service.',

                    'icon' => 'truck',

                    'image' => null,

                    'image_alt' => null,

                    'link_label' => null,

                    'link_url' => null,
                ],
            ],

            'columns' => 3,

            'alignment' => 'center',

            'background_color' => '#ffffff',

            'text_theme' => 'dark',
        ];
    }

    public function test_unsafe_feature_link_scheme_is_rejected(): void
    {
        $config = (
            new FeaturesBenefitsSection
        )->defaultConfigForTemplate(
            'icon_grid',
        );

        $config['items'][0]['link_label'] =
            'Read more';

        $config['items'][0]['link_url'] =
            'javascript:alert(1)';

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected unsafe feature link URL to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'items.0.link_url',
                $exception->errors(),
            );
        }
    }

    public function test_protocol_relative_feature_link_is_rejected(): void
    {
        $config = (
            new FeaturesBenefitsSection
        )->defaultConfigForTemplate(
            'icon_grid',
        );

        $config['items'][0]['link_label'] =
            'Read more';

        $config['items'][0]['link_url'] =
            '//evil.example.com';

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected protocol-relative feature link to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'items.0.link_url',
                $exception->errors(),
            );
        }
    }

    public function test_unsafe_feature_image_url_is_rejected(): void
    {
        $config = (
            new FeaturesBenefitsSection
        )->defaultConfigForTemplate(
            'image_grid',
        );

        $config['items'][0]['image'] =
            'data:image/svg+xml,<svg></svg>';

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected unsafe feature image URL to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'items.0.image',
                $exception->errors(),
            );
        }
    }

    public function test_unsupported_feature_icon_is_rejected(): void
    {
        $config = (
            new FeaturesBenefitsSection
        )->defaultConfigForTemplate(
            'icon_grid',
        );

        $config['items'][0]['icon'] =
            'arbitrary-custom-svg';

        try {
            (
                new FeaturesBenefitsConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                'Expected unsupported feature icon to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'items.0.icon',
                $exception->errors(),
            );
        }
    }

    public function test_safe_internal_anchor_and_https_media_are_accepted(): void
    {
        $config = (
            new FeaturesBenefitsSection
        )->defaultConfigForTemplate(
            'image_grid',
        );

        $config['items'][0]['link_label'] =
            'Shipping';

        $config['items'][0]['link_url'] =
            '#shipping';

        $config['items'][0]['image'] =
            'https://cdn.example.com/features/shipping.webp';

        $validated = (
            new FeaturesBenefitsConfigSchema
        )->validate(
            $config,
        );

        $this->assertSame(
            '#shipping',
            $validated['items'][0]['link_url'],
        );

        $this->assertSame(
            'https://cdn.example.com/features/shipping.webp',
            $validated['items'][0]['image'],
        );
    }
}
