<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Models\Admin;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class FooterBuilderMarketplaceRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_defaults_include_expected_promotion_popular_links_and_certifications(): void
    {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            );

        $this->assertSame(
            [
                'enabled' => true,

                'badge' => 'Flash Sale',

                'message' => 'Get 20% OFF your first order with code',

                'code' => 'WELCOME20',

                'button_label' => 'Shop Now',

                'button_url' => '#',
            ],
            $config[
                'promotion'
            ],
        );

        $this->assertSame(
            [
                [
                    'label' => 'Sneakers',

                    'url' => '#',
                ],
                [
                    'label' => 'Smartwatches',

                    'url' => '#',
                ],
                [
                    'label' => 'Denim Jackets',

                    'url' => '#',
                ],
                [
                    'label' => 'Wireless Earbuds',

                    'url' => '#',
                ],
                [
                    'label' => 'Backpacks',

                    'url' => '#',
                ],
                [
                    'label' => 'Sunglasses',

                    'url' => '#',
                ],
            ],
            $config[
                'popular_links'
            ],
        );

        $this->assertSame(
            [
                'SSL 256-Bit Encrypted',
                'PCI-DSS Level 1 Compliant',
            ],
            $config[
                'certifications'
            ],
        );
    }

    public function test_marketplace_popular_link_and_certification_order_is_preserved(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            $this->marketplaceConfig();

        $config[
            'popular_links'
        ] = [
            [
                'label' => 'Phones',

                'url' => '/phones',
            ],
            [
                'label' => 'Laptops',

                'url' => '/laptops',
            ],
            [
                'label' => 'Accessories',

                'url' => '/accessories',
            ],
        ];

        $config[
            'certifications'
        ] = [
            'Verified Marketplace',
            'Secure Checkout',
            'Buyer Protection',
        ];

        $this->updateFooter(
            admin: $admin,
            config: $config,
        )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $storedConfig =
            $this->storedConfig();

        $this->assertSame(
            [
                'Phones',
                'Laptops',
                'Accessories',
            ],
            array_column(
                $storedConfig[
                    'popular_links'
                ],
                'label',
            ),
        );

        $this->assertSame(
            [
                'Verified Marketplace',
                'Secure Checkout',
                'Buyer Protection',
            ],
            $storedConfig[
                'certifications'
            ],
        );
    }

    public function test_disabled_promotion_allows_empty_optional_content(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            $this->marketplaceConfig();

        $config[
            'promotion'
        ] = [
            'enabled' => false,

            'badge' => null,

            'message' => null,

            'code' => null,

            'button_label' => null,

            'button_url' => null,
        ];

        $this->updateFooter(
            admin: $admin,
            config: $config,
        )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $storedConfig =
            $this->storedConfig();

        $this->assertSame(
            [
                'enabled' => false,

                'badge' => null,

                'message' => null,

                'code' => null,

                'button_label' => null,

                'button_url' => null,
            ],
            $storedConfig[
                'promotion'
            ],
        );
    }

    public function test_enabled_promotion_still_requires_message_button_label_and_button_url(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            $this->marketplaceConfig();

        $config[
            'promotion'
        ] = [
            'enabled' => true,

            'badge' => 'Sale',

            'message' => null,

            'code' => 'SAVE20',

            'button_label' => null,

            'button_url' => null,
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::MarketplaceTrust
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'promotion.message',
                'promotion.button_label',
                'promotion.button_url',
            ]);
    }

    public function test_marketplace_supports_unicode_popular_links_and_certifications(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            $this->marketplaceConfig();

        $config[
            'popular_links'
        ] = [
            [
                'label' => 'ইলেকট্রনিক্স',

                'url' => '/electronics',
            ],
            [
                'label' => 'ফ্যাশন',

                'url' => '#fashion',
            ],
        ];

        $config[
            'certifications'
        ] = [
            'নিরাপদ পেমেন্ট',
            'যাচাইকৃত বিক্রেতা',
        ];

        $this->updateFooter(
            admin: $admin,
            config: $config,
        )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $storedConfig =
            $this->storedConfig();

        $this->assertSame(
            'ইলেকট্রনিক্স',
            $storedConfig[
                'popular_links'
            ][0][
                'label'
            ],
        );

        $this->assertSame(
            'ফ্যাশন',
            $storedConfig[
                'popular_links'
            ][1][
                'label'
            ],
        );

        $this->assertSame(
            [
                'নিরাপদ পেমেন্ট',
                'যাচাইকৃত বিক্রেতা',
            ],
            $storedConfig[
                'certifications'
            ],
        );
    }

    public function test_marketplace_supports_fragment_internal_and_https_urls(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            $this->marketplaceConfig();

        $config[
            'promotion'
        ][
            'button_url'
        ] =
            '/special-offers';

        $config[
            'popular_links'
        ] = [
            [
                'label' => 'Featured',

                'url' => '#featured',
            ],
            [
                'label' => 'Deals',

                'url' => '/deals',
            ],
            [
                'label' => 'External Store',

                'url' => 'https://example.com/store',
            ],
        ];

        $this->updateFooter(
            admin: $admin,
            config: $config,
        )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $storedConfig =
            $this->storedConfig();

        $this->assertSame(
            '/special-offers',
            $storedConfig[
                'promotion'
            ][
                'button_url'
            ],
        );

        $this->assertSame(
            '#featured',
            $storedConfig[
                'popular_links'
            ][0][
                'url'
            ],
        );

        $this->assertSame(
            '/deals',
            $storedConfig[
                'popular_links'
            ][1][
                'url'
            ],
        );

        $this->assertSame(
            'https://example.com/store',
            $storedConfig[
                'popular_links'
            ][2][
                'url'
            ],
        );
    }

    public function test_protocol_relative_promotion_url_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            $this->marketplaceConfig();

        $config[
            'promotion'
        ][
            'button_url'
        ] =
            '//evil.example';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::MarketplaceTrust
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'promotion.button_url',
            ]);
    }

    public function test_unsafe_popular_link_url_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            $this->marketplaceConfig();

        $config[
            'popular_links'
        ][0][
            'url'
        ] =
            'javascript:alert(1)';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::MarketplaceTrust
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'popular_links.0.url',
            ]);
    }

    public function test_empty_popular_links_and_certifications_are_allowed(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            $this->marketplaceConfig();

        $config[
            'popular_links'
        ] =
            [];

        $config[
            'certifications'
        ] =
            [];

        $this->updateFooter(
            admin: $admin,
            config: $config,
        )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $storedConfig =
            $this->storedConfig();

        $this->assertSame(
            [],
            $storedConfig[
                'popular_links'
            ],
        );

        $this->assertSame(
            [],
            $storedConfig[
                'certifications'
            ],
        );
    }

    public function test_marketplace_rejects_more_than_twelve_popular_links(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            $this->marketplaceConfig();

        $config[
            'popular_links'
        ] =
            array_map(
                static fn (
                    int $index,
                ): array => [
                    'label' => "Popular {$index}",

                    'url' => '#',
                ],
                range(
                    1,
                    13,
                ),
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::MarketplaceTrust
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'popular_links',
            ]);
    }

    public function test_marketplace_rejects_more_than_eight_certifications(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            $this->marketplaceConfig();

        $config[
            'certifications'
        ] =
            array_map(
                static fn (
                    int $index,
                ): string => "Certification {$index}",
                range(
                    1,
                    9,
                ),
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::MarketplaceTrust
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'certifications',
            ]);
    }

    public function test_marketplace_rejects_certification_longer_than_one_hundred_sixty_characters(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            $this->marketplaceConfig();

        $config[
            'certifications'
        ] = [
            str_repeat(
                'A',
                161,
            ),
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::MarketplaceTrust
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'certifications.0',
            ]);
    }

    public function test_marketplace_configuration_preserves_shared_footer_content(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            $this->marketplaceConfig();

        $config[
            'brand'
        ] = [
            'name' => 'Bangla Market',

            'description' => 'A trusted marketplace for customers and verified sellers.',
        ];

        $config[
            'copyright'
        ] = [
            'name' => 'Bangla Market Ltd.',

            'suffix' => 'All rights reserved.',
        ];

        $config[
            'developer'
        ] = [
            'prefix' => 'Created by',

            'name' => 'MicroWeb',

            'url' => 'https://example.com',
        ];

        $config[
            'social_links'
        ] = [
            [
                'platform' => 'facebook',

                'url' => '#facebook',
            ],
            [
                'platform' => 'youtube',

                'url' => '/youtube',
            ],
        ];

        $this->updateFooter(
            admin: $admin,
            config: $config,
        )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $storedConfig =
            $this->storedConfig();

        $this->assertSame(
            'Bangla Market',
            $storedConfig[
                'brand'
            ][
                'name'
            ],
        );

        $this->assertSame(
            'Bangla Market Ltd.',
            $storedConfig[
                'copyright'
            ][
                'name'
            ],
        );

        $this->assertSame(
            'MicroWeb',
            $storedConfig[
                'developer'
            ][
                'name'
            ],
        );

        $this->assertSame(
            [
                'facebook',
                'youtube',
            ],
            array_column(
                $storedConfig[
                    'social_links'
                ],
                'platform',
            ),
        );
    }

    public function test_marketplace_template_remains_selected_after_successful_update(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            $this->marketplaceConfig();

        $this->updateFooter(
            admin: $admin,
            config: $config,
        )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $this->assertSame(
            FooterTemplate::MarketplaceTrust,
            $footer->getAttribute(
                'template',
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function marketplaceConfig(): array
    {
        return (
            new FooterConfigDefaults
        )->for(
            FooterTemplate::MarketplaceTrust,
        );
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()->create([
            'role' => AdminRole::Admin,

            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function updateFooter(
        Admin $admin,
        array $config,
    ): TestResponse {
        return $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::MarketplaceTrust
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function storedConfig(): array
    {
        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $config =
            $footer->getAttribute(
                'config',
            );

        $this->assertIsArray(
            $config,
        );

        return $config;
    }
}
