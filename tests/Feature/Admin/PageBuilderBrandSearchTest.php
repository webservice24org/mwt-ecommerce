<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageBuilderBrandSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_search_active_brands(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,
            ]);

        $page =
            Page::factory()->create();

        $nike =
            Brand::factory()->create([
                'name' => 'Nike',

                'slug' => 'nike',

                'logo_path' => null,

                'is_active' => true,

                'position' => 20,
            ]);

        Brand::factory()->create([
            'name' => 'Adidas',

            'slug' => 'adidas',

            'is_active' => true,

            'position' => 10,
        ]);

        Brand::factory()->create([
            'name' => 'Nike Archive',

            'slug' => 'nike-archive',

            'is_active' => false,

            'position' => 5,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->getJson(
                route(
                    'admin.pages.builder.brands',
                    [
                        'page' => $page,

                        'search' => 'Nike',
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'brands',
            )
            ->assertJsonPath(
                'brands.0.id',
                $nike->id,
            )
            ->assertJsonPath(
                'brands.0.name',
                'Nike',
            )
            ->assertJsonPath(
                'brands.0.slug',
                'nike',
            )
            ->assertJsonPath(
                'brands.0.logo_url',
                null,
            )
            ->assertJsonPath(
                'brands.0.is_active',
                true,
            );
    }

    public function test_empty_search_returns_active_brands_in_catalog_order(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,
            ]);

        $page =
            Page::factory()->create();

        $second =
            Brand::factory()->create([
                'name' => 'Second',

                'is_active' => true,

                'position' => 20,
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'First',

                'is_active' => true,

                'position' => 10,
            ]);

        Brand::factory()->create([
            'name' => 'Hidden',

            'is_active' => false,

            'position' => 0,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->getJson(
                route(
                    'admin.pages.builder.brands',
                    $page,
                ),
            )
            ->assertOk()
            ->assertJsonCount(
                2,
                'brands',
            )
            ->assertJsonPath(
                'brands.0.id',
                $first->id,
            )
            ->assertJsonPath(
                'brands.1.id',
                $second->id,
            );
    }

    public function test_selected_ids_preserve_manual_order_and_include_inactive_brands(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,
            ]);

        $page =
            Page::factory()->create();

        $first =
            Brand::factory()->create([
                'name' => 'First Brand',

                'is_active' => true,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Second Brand',

                'is_active' => false,
            ]);

        $third =
            Brand::factory()->create([
                'name' => 'Third Brand',

                'is_active' => true,
            ]);

        $ids =
            implode(
                ',',
                [
                    $third->id,
                    $second->id,
                    $first->id,
                ],
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->getJson(
                route(
                    'admin.pages.builder.brands',
                    [
                        'page' => $page,

                        'ids' => $ids,
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonCount(
                3,
                'brands',
            )
            ->assertJsonPath(
                'brands.0.id',
                $third->id,
            )
            ->assertJsonPath(
                'brands.1.id',
                $second->id,
            )
            ->assertJsonPath(
                'brands.1.is_active',
                false,
            )
            ->assertJsonPath(
                'brands.2.id',
                $first->id,
            );
    }
}
