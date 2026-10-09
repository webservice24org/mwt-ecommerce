<?php

declare(strict_types=1);

namespace Tests\Feature\FooterBuilder;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class FooterStorefrontIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_footer_prop_is_null_when_no_footer_setting_exists(): void
    {
        $this
            ->get(
                route(
                    'home',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontFooter',
                        null,
                    ),
            );

        $this->assertDatabaseCount(
            'footer_settings',
            0,
        );
    }

    public function test_disabled_footer_is_not_shared_with_the_storefront(): void
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

        $this
            ->get(
                route(
                    'home',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontFooter',
                        null,
                    ),
            );
    }

    #[DataProvider('footerTemplates')]
    public function test_enabled_footer_is_shared_with_the_storefront(
        FooterTemplate $template,
    ): void {
        $config =
            $this->defaults(
                $template,
            );

        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => $template,

                'config' => $config,

                'is_enabled' => true,
            ]);

        $this
            ->get(
                route(
                    'home',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontFooter.template',
                        $template->value,
                    )
                    ->where(
                        'storefrontFooter.config',
                        $config,
                    ),
            );
    }

    public function test_storefront_receives_saved_shared_bottom_footer_configuration(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'copyright'
        ] = [
            'name' => 'MWT Commerce',

            'suffix' => 'All rights reserved.',
        ];

        $config[
            'social_links'
        ] = [
            [
                'platform' => 'facebook',

                'url' => 'https://example.com/facebook',
            ],
            [
                'platform' => 'youtube',

                'url' => 'https://example.com/youtube',
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

        $this
            ->get(
                route(
                    'home',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontFooter.template',
                        FooterTemplate::MarketplaceTrust->value,
                    )
                    ->where(
                        'storefrontFooter.config.copyright.name',
                        'MWT Commerce',
                    )
                    ->where(
                        'storefrontFooter.config.copyright.suffix',
                        'All rights reserved.',
                    )
                    ->where(
                        'storefrontFooter.config.social_links.0.platform',
                        'facebook',
                    )
                    ->where(
                        'storefrontFooter.config.social_links.1.platform',
                        'youtube',
                    )
                    ->where(
                        'storefrontFooter.config.developer.prefix',
                        'Created by',
                    )
                    ->where(
                        'storefrontFooter.config.developer.name',
                        'MicroWeb',
                    )
                    ->where(
                        'storefrontFooter.config.developer.url',
                        'https://example.com',
                    ),
            );
    }

    public function test_storefront_uses_the_latest_saved_footer_configuration(): void
    {
        $originalConfig =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $footer =
            FooterSetting::query()
                ->create([
                    'singleton_key' => FooterSetting::SINGLETON_KEY,

                    'template' => FooterTemplate::LuxeNewsletter,

                    'config' => $originalConfig,

                    'is_enabled' => true,
                ]);

        $updatedConfig =
            $this->defaults(
                FooterTemplate::MinimalLocalized,
            );

        $updatedConfig[
            'brand'
        ][
            'name'
        ] =
            'Updated Store';

        $footer->update([
            'template' => FooterTemplate::MinimalLocalized,

            'config' => $updatedConfig,
        ]);

        $this
            ->get(
                route(
                    'home',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontFooter.template',
                        FooterTemplate::MinimalLocalized->value,
                    )
                    ->where(
                        'storefrontFooter.config.brand.name',
                        'Updated Store',
                    ),
            );
    }

    public function test_disabling_an_existing_footer_hides_it_without_deleting_its_configuration(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        $footer =
            FooterSetting::query()
                ->create([
                    'singleton_key' => FooterSetting::SINGLETON_KEY,

                    'template' => FooterTemplate::MarketplaceTrust,

                    'config' => $config,

                    'is_enabled' => true,
                ]);

        $footer->update([
            'is_enabled' => false,
        ]);

        $this
            ->get(
                route(
                    'home',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontFooter',
                        null,
                    ),
            );

        $footer->refresh();

        $this->assertSame(
            FooterTemplate::MarketplaceTrust,
            $footer->getAttribute(
                'template',
            ),
        );

        $this->assertSame(
            $config,
            $footer->getAttribute(
                'config',
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
}
