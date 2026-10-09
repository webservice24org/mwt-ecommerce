<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterBuilderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_footer_builder(): void
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
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertOk();
    }

    public function test_manager_can_view_footer_builder(): void
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
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertOk();
    }

    public function test_editor_can_view_footer_builder(): void
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
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertOk();
    }

    public function test_admin_can_update_footer_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Admin,
            );

        $response =
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
                        'template' => 'luxe_newsletter',

                        'is_enabled' => true,

                        'config' => [],
                    ],
                );

        $response->assertRedirect(
            route(
                'admin.website-settings.footer-builder.edit',
            ),
        );
    }

    public function test_manager_can_update_footer_builder(): void
    {
        $admin =
            $this->createAdmin(
                AdminRole::Manager,
            );

        $response =
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
                        'template' => 'luxe_newsletter',

                        'is_enabled' => true,

                        'config' => [],
                    ],
                );

        $response->assertRedirect(
            route(
                'admin.website-settings.footer-builder.edit',
            ),
        );
    }

    public function test_editor_cannot_update_footer_builder(): void
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
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => 'luxe_newsletter',

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
