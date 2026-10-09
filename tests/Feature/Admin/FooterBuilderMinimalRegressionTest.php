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

final class FooterBuilderMinimalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimal_defaults_include_enabled_localization(): void
    {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $this->assertIsArray(
            $config['localization'],
        );

        $this->assertTrue(
            $config[
                'localization'
            ][
                'enabled'
            ],
        );

        $this->assertNotEmpty(
            $config[
                'localization'
            ][
                'languages'
            ],
        );

        $this->assertNotEmpty(
            $config[
                'localization'
            ][
                'currencies'
            ],
        );
    }

    public function test_language_and_currency_order_is_preserved(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $config['localization'] = [
            'enabled' => true,

            'languages' => [
                [
                    'code' => 'bn-BD',

                    'label' => 'বাংলা',
                ],
                [
                    'code' => 'en-US',

                    'label' => 'English (US)',
                ],
                [
                    'code' => 'de',

                    'label' => 'Deutsch',
                ],
            ],

            'currencies' => [
                [
                    'code' => 'BDT',

                    'label' => 'BDT (৳)',
                ],
                [
                    'code' => 'USD',

                    'label' => 'USD ($)',
                ],
                [
                    'code' => 'EUR',

                    'label' => 'EUR (€)',
                ],
            ],
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
                    'template' => FooterTemplate::MinimalLocalized
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
            [
                'bn-BD',
                'en-US',
                'de',
            ],
            array_column(
                $storedConfig[
                    'localization'
                ][
                    'languages'
                ],
                'code',
            ),
        );

        $this->assertSame(
            [
                'BDT',
                'USD',
                'EUR',
            ],
            array_column(
                $storedConfig[
                    'localization'
                ][
                    'currencies'
                ],
                'code',
            ),
        );
    }

    public function test_localization_supports_unicode_labels(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $config['localization'] = [
            'enabled' => true,

            'languages' => [
                [
                    'code' => 'bn-BD',

                    'label' => 'বাংলা',
                ],
            ],

            'currencies' => [
                [
                    'code' => 'BDT',

                    'label' => 'বাংলাদেশি টাকা (৳)',
                ],
            ],
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
                    'template' => FooterTemplate::MinimalLocalized
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
            'বাংলা',
            $storedConfig[
                'localization'
            ][
                'languages'
            ][0][
                'label'
            ],
        );

        $this->assertSame(
            'বাংলাদেশি টাকা (৳)',
            $storedConfig[
                'localization'
            ][
                'currencies'
            ][0][
                'label'
            ],
        );
    }

    public function test_disabled_localization_may_have_empty_option_lists(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $config['localization'] = [
            'enabled' => false,

            'languages' => [],

            'currencies' => [],
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
                    'template' => FooterTemplate::MinimalLocalized
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

        $this->assertFalse(
            $storedConfig[
                'localization'
            ][
                'enabled'
            ],
        );

        $this->assertSame(
            [],
            $storedConfig[
                'localization'
            ][
                'languages'
            ],
        );

        $this->assertSame(
            [],
            $storedConfig[
                'localization'
            ][
                'currencies'
            ],
        );
    }

    public function test_supported_code_characters_are_preserved(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $config['localization'] = [
            'enabled' => true,

            'languages' => [
                [
                    'code' => 'zh_Hant-HK',

                    'label' => 'Traditional Chinese',
                ],
            ],

            'currencies' => [
                [
                    'code' => 'USD_01',

                    'label' => 'US Dollar',
                ],
            ],
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
                    'template' => FooterTemplate::MinimalLocalized
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
            'zh_Hant-HK',
            $storedConfig[
                'localization'
            ][
                'languages'
            ][0][
                'code'
            ],
        );

        $this->assertSame(
            'USD_01',
            $storedConfig[
                'localization'
            ][
                'currencies'
            ][0][
                'code'
            ],
        );
    }

    public function test_invalid_code_characters_are_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $config[
            'localization'
        ][
            'currencies'
        ][0][
            'code'
        ] =
            'USD $';

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
                    'template' => FooterTemplate::MinimalLocalized
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
                'localization.currencies.0.code',
            ]);
    }

    public function test_minimal_configuration_preserves_shared_footer_content(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $config['brand'] = [
            'name' => 'Nordic Raw',

            'description' => 'Minimal products built for everyday use.',
        ];

        $config['copyright'] = [
            'name' => 'Nordic Raw Ltd.',

            'suffix' => 'All rights reserved.',
        ];

        $config['developer'] = [
            'prefix' => 'Engineered by',

            'name' => 'Apex Digital',

            'url' => 'https://example.com',
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
                    'template' => FooterTemplate::MinimalLocalized
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
            'Nordic Raw',
            $storedConfig[
                'brand'
            ][
                'name'
            ],
        );

        $this->assertSame(
            'Nordic Raw Ltd.',
            $storedConfig[
                'copyright'
            ][
                'name'
            ],
        );

        $this->assertSame(
            'Apex Digital',
            $storedConfig[
                'developer'
            ][
                'name'
            ],
        );
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()->create([
            'role' => AdminRole::Admin,

            'is_active' => true,
        ]);
    }
}
