<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Models\Admin;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterBuilderMarketplaceTrustTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_configuration_can_be_updated(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            );

        $config['promotion'] = [
            'enabled' => true,

            'badge' => 'Weekend Deal',

            'message' => 'Save 25% on selected products with code',

            'code' => 'WEEKEND25',

            'button_label' => 'Shop Deals',

            'button_url' => '/deals',
        ];

        $config['popular_links'] = [
            [
                'label' => 'Laptops',

                'url' => '/category/laptops',
            ],
            [
                'label' => 'Smartphones',

                'url' => '#phones',
            ],
        ];

        $config['certifications'] = [
            'SSL 256-Bit Encrypted',
            'Verified Marketplace',
        ];

        $this
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
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $storedConfig =
            $footer->getAttribute(
                'config',
            );

        $this->assertIsArray(
            $storedConfig,
        );

        $this->assertSame(
            $config[
                'promotion'
            ],
            $storedConfig[
                'promotion'
            ],
        );

        $this->assertSame(
            $config[
                'popular_links'
            ],
            $storedConfig[
                'popular_links'
            ],
        );

        $this->assertSame(
            $config[
                'certifications'
            ],
            $storedConfig[
                'certifications'
            ],
        );
    }

    public function test_enabled_promotion_requires_message_button_label_and_button_url(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            );

        $config['promotion'] = [
            'enabled' => true,

            'badge' => '',

            'message' => '',

            'code' => '',

            'button_label' => '',

            'button_url' => '',
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

    public function test_unsafe_promotion_url_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'promotion'
        ][
            'button_url'
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
                'promotion.button_url',
            ]);
    }

    public function test_more_than_twelve_popular_links_are_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'popular_links'
        ] =
            array_fill(
                0,
                13,
                [
                    'label' => 'Popular',

                    'url' => '#',
                ],
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

    public function test_more_than_eight_certifications_are_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'certifications'
        ] =
            array_fill(
                0,
                9,
                'Verified',
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

    private function createAdmin(): Admin
    {
        return Admin::factory()->create([
            'role' => AdminRole::Admin,

            'is_active' => true,
        ]);
    }
}
