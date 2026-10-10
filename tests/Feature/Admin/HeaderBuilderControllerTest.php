<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\HeaderConfigDefaults;
use App\Models\Admin;
use App\Models\HeaderSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class HeaderBuilderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_returns_header_builder_contract(): void
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
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ): Assert => $page
                    ->component(
                        'Admin/HeaderBuilder/Edit',
                    )
                    ->where(
                        'header.template',
                        'mega_menu',
                    )
                    ->where(
                        'header.is_enabled',
                        true,
                    )
                    ->has(
                        'header.config',
                    )
                    ->has(
                        'templates',
                        1,
                    )
                    ->where(
                        'templates.0.key',
                        'mega_menu',
                    )
                    ->where(
                        'templates.0.label',
                        'Mega Menu',
                    )
                    ->where(
                        'abilities.update',
                        true,
                    ),
            );

        $this->assertDatabaseCount(
            'header_settings',
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
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ): Assert => $page
                    ->component(
                        'Admin/HeaderBuilder/Edit',
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

        HeaderSetting::query()->create([
            'singleton_key' => HeaderSetting::SINGLETON_KEY,

            'template' => HeaderTemplate::MegaMenu,

            'config' => [],

            'is_enabled' => true,
        ]);

        $expected = (
            new HeaderConfigDefaults
        )->for(
            HeaderTemplate::MegaMenu,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ): Assert => $page
                    ->where(
                        'header.template',
                        'mega_menu',
                    )
                    ->where(
                        'header.config',
                        $expected,
                    ),
            );

        /*
         * GET normalization must not silently
         * overwrite persisted configuration.
         */
        $header =
            HeaderSetting::query()
                ->firstOrFail();

        $this->assertSame(
            [],
            $header->config,
        );
    }

    public function test_update_persists_normalized_header_configuration(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        HeaderSetting::singleton();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.website-settings.header-builder.update',
                ),
                [
                    'template' => HeaderTemplate::MegaMenu->value,

                    'is_enabled' => false,

                    /*
                     * Empty config intentionally
                     * normalizes to template defaults.
                     */
                    'config' => [],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->assertSessionHas(
                'success',
                'Header settings updated successfully.',
            );

        $header =
            HeaderSetting::query()
                ->firstOrFail();

        $this->assertSame(
            HeaderTemplate::MegaMenu,
            $header->template,
        );

        $this->assertFalse(
            $header->is_enabled,
        );

        $this->assertSame(
            (
                new HeaderConfigDefaults
            )->for(
                HeaderTemplate::MegaMenu,
            ),
            $header->config,
        );
    }

    public function test_invalid_template_is_rejected(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        HeaderSetting::singleton();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.header-builder.update',
                ),
                [
                    'template' => 'unknown_header',

                    'is_enabled' => true,

                    'config' => [],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'template',
            ]);

        $header =
            HeaderSetting::query()
                ->firstOrFail();

        $this->assertSame(
            HeaderTemplate::MegaMenu,
            $header->template,
        );
    }

    public function test_invalid_config_is_not_persisted(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        HeaderSetting::singleton();

        $original =
            HeaderSetting::query()
                ->firstOrFail()
                ->config;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.header-builder.update',
                ),
                [
                    'template' => HeaderTemplate::MegaMenu->value,

                    'is_enabled' => true,

                    'config' => [
                        'custom_css' => 'position: fixed;',
                    ],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'custom_css',
            ]);

        $header =
            HeaderSetting::query()
                ->firstOrFail();

        $this->assertSame(
            HeaderTemplate::MegaMenu,
            $header->template,
        );

        $this->assertSame(
            $original,
            $header->config,
        );
    }
}
