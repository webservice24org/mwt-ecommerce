<?php

declare(strict_types=1);

namespace Tests\Unit\FooterBuilder;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Domain\FooterBuilder\Schemas\FooterConfigSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class FooterSharedBottomRegressionTest extends TestCase
{
    #[DataProvider('footerTemplates')]
    public function test_every_footer_template_contains_the_shared_bottom_contract(
        FooterTemplate $template,
    ): void {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                $template,
            );

        $this->assertArrayHasKey(
            'brand',
            $config,
        );

        $this->assertArrayHasKey(
            'social_links',
            $config,
        );

        $this->assertArrayHasKey(
            'copyright',
            $config,
        );

        $this->assertArrayHasKey(
            'developer',
            $config,
        );

        $this->assertIsArray(
            $config['brand'],
        );

        $this->assertIsArray(
            $config['social_links'],
        );

        $this->assertIsArray(
            $config['copyright'],
        );

        $this->assertIsArray(
            $config['developer'],
        );

        $this->assertArrayHasKey(
            'name',
            $config['brand'],
        );

        $this->assertArrayHasKey(
            'name',
            $config['copyright'],
        );

        $this->assertArrayHasKey(
            'suffix',
            $config['copyright'],
        );

        $this->assertArrayHasKey(
            'prefix',
            $config['developer'],
        );

        $this->assertArrayHasKey(
            'name',
            $config['developer'],
        );

        $this->assertArrayHasKey(
            'url',
            $config['developer'],
        );
    }

    #[DataProvider('footerTemplates')]
    public function test_shared_bottom_configuration_is_preserved_by_the_schema(
        FooterTemplate $template,
    ): void {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                $template,
            );

        $config['brand'] = [
            'name' => 'Shared Store',

            'description' => 'Shared footer regression test.',
        ];

        $config['social_links'] = [
            [
                'platform' => 'facebook',

                'url' => '#facebook',
            ],
            [
                'platform' => 'instagram',

                'url' => '/instagram',
            ],
            [
                'platform' => 'youtube',

                'url' => 'https://example.com/youtube',
            ],
        ];

        $config['copyright'] = [
            'name' => 'Shared Store Ltd.',

            'suffix' => 'All rights reserved.',
        ];

        $config['developer'] = [
            'prefix' => 'Created by',

            'name' => 'MicroWeb',

            'url' => 'https://example.com',
        ];

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                template: $template,
                config: $config,
            );

        $this->assertSame(
            [
                'name' => 'Shared Store',

                'description' => 'Shared footer regression test.',
            ],
            $validated['brand'],
        );

        $this->assertSame(
            [
                [
                    'platform' => 'facebook',

                    'url' => '#facebook',
                ],
                [
                    'platform' => 'instagram',

                    'url' => '/instagram',
                ],
                [
                    'platform' => 'youtube',

                    'url' => 'https://example.com/youtube',
                ],
            ],
            $validated['social_links'],
        );

        $this->assertSame(
            [
                'name' => 'Shared Store Ltd.',

                'suffix' => 'All rights reserved.',
            ],
            $validated['copyright'],
        );

        $this->assertSame(
            [
                'prefix' => 'Created by',

                'name' => 'MicroWeb',

                'url' => 'https://example.com',
            ],
            $validated['developer'],
        );
    }

    #[DataProvider('footerTemplates')]
    public function test_shared_bottom_allows_no_social_links(
        FooterTemplate $template,
    ): void {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                $template,
            );

        $config[
            'social_links'
        ] =
            [];

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                template: $template,
                config: $config,
            );

        $this->assertSame(
            [],
            $validated[
                'social_links'
            ],
        );
    }

    #[DataProvider('footerTemplates')]
    public function test_developer_url_may_be_null(
        FooterTemplate $template,
    ): void {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                $template,
            );

        $config[
            'developer'
        ][
            'url'
        ] =
            null;

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                template: $template,
                config: $config,
            );

        $this->assertNull(
            $validated[
                'developer'
            ][
                'url'
            ],
        );
    }

    #[DataProvider('footerTemplates')]
    public function test_shared_bottom_supports_unicode_content(
        FooterTemplate $template,
    ): void {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                $template,
            );

        $config['brand'] = [
            'name' => 'বাংলা বাজার',

            'description' => 'বিশ্বস্ত অনলাইন মার্কেটপ্লেস',
        ];

        $config['copyright'] = [
            'name' => 'বাংলা বাজার লিমিটেড',

            'suffix' => 'সর্বস্বত্ব সংরক্ষিত।',
        ];

        $config['developer'] = [
            'prefix' => 'তৈরি করেছে',

            'name' => 'মাইক্রোওয়েব',

            'url' => '#developer',
        ];

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                template: $template,
                config: $config,
            );

        $this->assertSame(
            'বাংলা বাজার',
            $validated[
                'brand'
            ][
                'name'
            ],
        );

        $this->assertSame(
            'বাংলা বাজার লিমিটেড',
            $validated[
                'copyright'
            ][
                'name'
            ],
        );

        $this->assertSame(
            'সর্বস্বত্ব সংরক্ষিত।',
            $validated[
                'copyright'
            ][
                'suffix'
            ],
        );

        $this->assertSame(
            'তৈরি করেছে',
            $validated[
                'developer'
            ][
                'prefix'
            ],
        );

        $this->assertSame(
            'মাইক্রোওয়েব',
            $validated[
                'developer'
            ][
                'name'
            ],
        );
    }

    #[DataProvider('footerTemplates')]
    public function test_shared_bottom_preserves_social_link_order(
        FooterTemplate $template,
    ): void {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                $template,
            );

        $config[
            'social_links'
        ] = [
            [
                'platform' => 'youtube',

                'url' => '#youtube',
            ],
            [
                'platform' => 'facebook',

                'url' => '#facebook',
            ],
            [
                'platform' => 'linkedin',

                'url' => '#linkedin',
            ],
            [
                'platform' => 'instagram',

                'url' => '#instagram',
            ],
        ];

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                template: $template,
                config: $config,
            );

        $this->assertSame(
            [
                'youtube',
                'facebook',
                'linkedin',
                'instagram',
            ],
            array_column(
                $validated[
                    'social_links'
                ],
                'platform',
            ),
        );
    }

    /**
     * @return array<
     *     string,
     *     array{
     *         0: FooterTemplate
     *     }
     * >
     */
    public static function footerTemplates(): array
    {
        return [
            'luxe newsletter' => [
                FooterTemplate::LuxeNewsletter,
            ],

            'minimal localized' => [
                FooterTemplate::MinimalLocalized,
            ],

            'marketplace trust' => [
                FooterTemplate::MarketplaceTrust,
            ],
        ];
    }
}
