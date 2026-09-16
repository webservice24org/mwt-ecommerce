<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_brand_management(): void
    {
        $this
            ->get(route('admin.brands.index'))
            ->assertRedirect(
                route('admin.login'),
            );
    }

    public function test_editor_can_view_brands(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $this
            ->actingAs($editor, 'admin')
            ->get(route('admin.brands.index'))
            ->assertOk();
    }

    public function test_editor_can_create_brand(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $this
            ->actingAs($editor, 'admin')
            ->post(
                route('admin.brands.store'),
                [
                    'name' => 'Samsung',
                    'slug' => '',
                    'description' => null,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'brands',
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
            ],
        );
    }

    public function test_editor_can_update_brand(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $brand = Brand::factory()->create();

        $this
            ->actingAs($editor, 'admin')
            ->put(
                route(
                    'admin.brands.update',
                    $brand,
                ),
                [
                    'name' => 'Updated Brand',
                    'slug' => '',
                    'description' => null,
                    'position' => 1,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'brands',
            [
                'id' => $brand->id,
                'name' => 'Updated Brand',
                'slug' => 'updated-brand',
            ],
        );
    }

    public function test_editor_cannot_delete_brand(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $brand = Brand::factory()->create();

        $this
            ->actingAs($editor, 'admin')
            ->delete(
                route(
                    'admin.brands.destroy',
                    $brand,
                ),
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'brands',
            [
                'id' => $brand->id,
            ],
        );
    }

    public function test_manager_can_delete_brand(): void
    {
        $manager = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $brand = Brand::factory()->create();

        $this
            ->actingAs($manager, 'admin')
            ->delete(
                route(
                    'admin.brands.destroy',
                    $brand,
                ),
            )
            ->assertRedirect(
                route('admin.brands.index'),
            );

        $this->assertDatabaseMissing(
            'brands',
            [
                'id' => $brand->id,
            ],
        );
    }
}
