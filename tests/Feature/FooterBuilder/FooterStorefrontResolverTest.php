<?php

declare(strict_types=1);

namespace Tests\Feature\FooterBuilder;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Domain\FooterBuilder\Storefront\FooterStorefrontResolver;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterStorefrontResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_footer_returns_null_without_creating_the_singleton(): void
    {
        $this->assertDatabaseCount(
            'footer_settings',
            0,
        );

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNull(
            $resolved,
        );

        /*
         * Storefront resolution must remain
         * read-only.
         */
        $this->assertDatabaseCount(
            'footer_settings',
            0,
        );
    }

    public function test_disabled_footer_is_not_exposed_to_storefront(): void
    {
        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::LuxeNewsletter,

                'config' => $this->defaults(
                    FooterTemplate::LuxeNewsletter,
                ),

                'is_enabled' => false,
            ]);

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNull(
            $resolved,
        );
    }

    public function test_enabled_luxe_footer_is_resolved_for_storefront(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::LuxeNewsletter,

                'config' => $config,

                'is_enabled' => true,
            ]);

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            'luxe_newsletter',
            $resolved[
                'template'
            ],
        );

        $this->assertSame(
            $config,
            $resolved[
                'config'
            ],
        );
    }

    public function test_enabled_minimal_footer_is_resolved_for_storefront(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MinimalLocalized,
            );

        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::MinimalLocalized,

                'config' => $config,

                'is_enabled' => true,
            ]);

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            'minimal_localized',
            $resolved[
                'template'
            ],
        );

        $this->assertSame(
            $config,
            $resolved[
                'config'
            ],
        );
    }

    public function test_enabled_marketplace_footer_is_resolved_for_storefront(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::MarketplaceTrust,

                'config' => $config,

                'is_enabled' => true,
            ]);

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            'marketplace_trust',
            $resolved[
                'template'
            ],
        );

        $this->assertSame(
            $config,
            $resolved[
                'config'
            ],
        );
    }

    public function test_empty_stored_config_is_normalized_for_storefront_without_persisting_changes(): void
    {
        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::MarketplaceTrust,

                'config' => [],

                'is_enabled' => true,
            ]);

        $expected =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            'marketplace_trust',
            $resolved[
                'template'
            ],
        );

        $this->assertSame(
            $expected,
            $resolved[
                'config'
            ],
        );

        /*
         * Normalization for rendering must
         * never rewrite persisted data during
         * a public request.
         */
        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $this->assertSame(
            [],
            $footer->config,
        );
    }

    public function test_storefront_resolution_preserves_shared_bottom_footer_data(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'copyright'
        ] = [
            'name' => 'Storefront Ltd.',

            'suffix' => 'All rights reserved.',
        ];

        $config[
            'social_links'
        ] = [
            [
                'platform' => 'facebook',

                'url' => '#facebook',
            ],
            [
                'platform' => 'instagram',

                'url' => 'https://example.com/instagram',
            ],
        ];

        $config[
            'developer'
        ] = [
            'prefix' => 'Created by',

            'name' => 'MicroWeb',

            'url' => 'https://example.com',
        ];

        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::MarketplaceTrust,

                'config' => $config,

                'is_enabled' => true,
            ]);

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNotNull(
            $resolved,
        );

        $resolvedConfig =
            $resolved[
                'config'
            ];

        $this->assertSame(
            'Storefront Ltd.',
            $resolvedConfig[
                'copyright'
            ][
                'name'
            ],
        );

        $this->assertSame(
            [
                'facebook',
                'instagram',
            ],
            array_column(
                $resolvedConfig[
                    'social_links'
                ],
                'platform',
            ),
        );

        $this->assertSame(
            'MicroWeb',
            $resolvedConfig[
                'developer'
            ][
                'name'
            ],
        );
    }

    public function test_malformed_persisted_configuration_does_not_break_storefront_resolution(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        /*
         * Simulate legacy/corrupted data that
         * bypassed the normal Admin validation.
         */
        $config[
            'unsupported_field'
        ] =
            'should not render';

        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::MarketplaceTrust,

                'config' => $config,

                'is_enabled' => true,
            ]);

        $resolved =
            $this->resolver()
                ->resolve();

        $this->assertNull(
            $resolved,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(
        FooterTemplate $template,
    ): array {
        return (
            new FooterConfigDefaults
        )->for(
            $template,
        );
    }

    private function resolver(): FooterStorefrontResolver
    {
        return $this->app->make(
            FooterStorefrontResolver::class,
        );
    }
}
