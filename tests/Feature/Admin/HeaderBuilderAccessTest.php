<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HeaderBuilderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_header_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Admin,
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
            ->assertOk();
    }

    public function test_manager_can_view_header_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Manager,
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
            ->assertOk();
    }

    public function test_editor_can_view_header_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Editor,
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
            ->assertOk();
    }

    public function test_admin_can_update_header_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Admin,
            );

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
                    'template' => 'mega_menu',

                    'is_enabled' => true,

                    'config' => [],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            );
    }

    public function test_manager_can_update_header_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Manager,
            );

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
                    'template' => 'mega_menu',

                    'is_enabled' => true,

                    'config' => [],
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.header-builder.edit',
                ),
            );
    }

    public function test_editor_cannot_update_header_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Editor,
            );

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
                    'template' => 'mega_menu',

                    'is_enabled' => true,

                    'config' => [],
                ],
            )
            ->assertForbidden();
    }

    private function createAdmin(
        AdminRole $role,
    ): Admin {
        return Admin::factory()->create([
            'role' => $role,

            'is_active' => true,
        ]);
    }
}
