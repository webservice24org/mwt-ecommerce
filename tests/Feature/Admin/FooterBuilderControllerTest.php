<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Models\Admin;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class FooterBuilderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_returns_footer_builder_contract(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ): Assert => $page
                    ->component(
                        'Admin/FooterBuilder/Edit',
                    )
                    ->where(
                        'footer.template',
                        'luxe_newsletter',
                    )
                    ->where(
                        'footer.is_enabled',
                        true,
                    )
                    ->has(
                        'footer.config',
                    )
                    ->has(
                        'templates',
                        3,
                    )
                    ->where(
                        'templates.0.key',
                        'luxe_newsletter',
                    )
                    ->where(
                        'templates.1.key',
                        'minimal_localized',
                    )
                    ->where(
                        'templates.2.key',
                        'marketplace_trust',
                    )
                    ->where(
                        'abilities.update',
                        true,
                    ),
            );

        $this->assertDatabaseCount(
            'footer_settings',
            1,
        );
    }

    public function test_editor_receives_read_only_ability(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,

                'is_active' => true,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ): Assert => $page
                    ->component(
                        'Admin/FooterBuilder/Edit',
                    )
                    ->where(
                        'abilities.update',
                        false,
                    ),
            );
    }

    public function test_empty_stored_config_is_normalized_for_editor(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        FooterSetting::query()->create([
            'singleton_key' => FooterSetting::SINGLETON_KEY,

            'template' => FooterTemplate::MinimalLocalized,

            'config' => [],

            'is_enabled' => true,
        ]);

        $expected =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ): Assert => $page
                    ->component(
                        'Admin/FooterBuilder/Edit',
                    )
                    ->where(
                        'footer.template',
                        'minimal_localized',
                    )
                    ->where(
                        'footer.config',
                        $expected,
                    ),
            );

        /*
         * GET normalization must not silently
         * overwrite the persisted record.
         */
        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $this->assertSame(
            [],
            $footer->config,
        );
    }

    public function test_update_persists_normalized_footer_configuration(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        FooterSetting::singleton();

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

                    'is_enabled' => false,

                    /*
                     * Empty config is intentionally
                     * normalized to the selected
                     * template defaults.
                     */
                    'config' => [],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHas(
                'success',
                'Footer settings updated successfully.',
            );

        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $this->assertSame(
            FooterTemplate::MarketplaceTrust,
            $footer->template,
        );

        $this->assertFalse(
            $footer->is_enabled,
        );

        $this->assertSame(
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            ),
            $footer->config,
        );
    }

    public function test_invalid_template_is_rejected(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        FooterSetting::singleton();

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
                    'template' => 'unknown_footer',

                    'is_enabled' => true,

                    'config' => [],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'template',
            ]);

        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $this->assertSame(
            FooterTemplate::LuxeNewsletter,
            $footer->template,
        );
    }

    public function test_invalid_config_is_not_persisted(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        FooterSetting::singleton();

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
                    'template' => FooterTemplate::LuxeNewsletter
                        ->value,

                    'is_enabled' => true,

                    'config' => [
                        'custom_css' => 'position: fixed;',
                    ],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'custom_css',
            ]);

        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $this->assertSame(
            FooterTemplate::LuxeNewsletter,
            $footer->template,
        );

        $this->assertSame(
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            ),
            $footer->config,
        );
    }
}
