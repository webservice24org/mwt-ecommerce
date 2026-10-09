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

final class FooterBuilderMinimalLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimal_localization_configuration_can_be_updated(): void
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
                    'code' => 'en-US',

                    'label' => 'English (US)',
                ],
                [
                    'code' => 'bn-BD',

                    'label' => 'বাংলা',
                ],
            ],

            'currencies' => [
                [
                    'code' => 'USD',

                    'label' => 'USD ($)',
                ],
                [
                    'code' => 'BDT',

                    'label' => 'BDT (৳)',
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
            $config[
                'localization'
            ],
            $storedConfig[
                'localization'
            ],
        );
    }

    public function test_enabled_localization_requires_at_least_one_language_and_currency(): void
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

        $config['localization'] = [
            'enabled' => true,

            'languages' => [],

            'currencies' => [],
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
                'localization.languages',
                'localization.currencies',
            ]);
    }

    public function test_invalid_localization_code_is_rejected(): void
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
            'languages'
        ][0][
            'code'
        ] =
            'en US!';

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
                'localization.languages.0.code',
            ]);
    }

    public function test_more_than_twelve_language_options_are_rejected(): void
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
            'languages'
        ] =
            array_fill(
                0,
                13,
                [
                    'code' => 'en',

                    'label' => 'English',
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
                'localization.languages',
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
