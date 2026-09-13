<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_view_product_index(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->get(
                route(
                    'admin.products.index',
                ),
            )
            ->assertOk();
    }

    public function test_guest_cannot_access_product_admin(): void
    {
        $this->get(
            route(
                'admin.products.index',
            ),
        )->assertRedirect(
            route('admin.login'),
        );
    }

    public function test_manager_can_create_product(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $brand = Brand::factory()->create();

        $categories = Category::factory()
            ->count(2)
            ->create();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.store',
                ),
                [
                    'brand_id' => $brand->id,
                    'name' => 'MacBook Pro',
                    'slug' => '',
                    'short_description' => 'Laptop',
                    'description' => 'Full product description',
                    'status' => ProductStatus::Draft->value,
                    'is_featured' => true,
                    'position' => 0,
                    'published_at' => null,
                    'meta_title' => 'MacBook Pro',
                    'meta_description' => 'MacBook Pro product.',
                    'category_ids' => $categories
                        ->pluck('id')
                        ->all(),
                ],
            );

        $product = Product::query()
            ->where(
                'name',
                'MacBook Pro',
            )
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'admin.products.edit',
                $product,
            ),
        );

        $this->assertSame(
            'macbook-pro',
            $product->slug,
        );

        $this->assertCount(
            2,
            $product->categories,
        );
    }

    public function test_editor_can_create_and_update_product(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $product =
            Product::factory()->create();

        $this->actingAs(
            $editor,
            'admin',
        )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                [
                    'brand_id' => null,
                    'name' => 'Updated Product',
                    'slug' => $product->slug,
                    'short_description' => null,
                    'description' => null,
                    'status' => ProductStatus::Draft->value,
                    'is_featured' => false,
                    'position' => 0,
                    'published_at' => null,
                    'meta_title' => null,
                    'meta_description' => null,
                    'category_ids' => [],
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
                'name' => 'Updated Product',
            ],
        );
    }

    public function test_editor_cannot_delete_product(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $product =
            Product::factory()->create();

        $this->actingAs(
            $editor,
            'admin',
        )
            ->delete(
                route(
                    'admin.products.destroy',
                    $product,
                ),
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
            ],
        );
    }

    public function test_manager_can_delete_product(): void
    {
        $manager = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $product =
            Product::factory()->create();

        $this->actingAs(
            $manager,
            'admin',
        )
            ->delete(
                route(
                    'admin.products.destroy',
                    $product,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.products.index',
                ),
            );

        $this->assertDatabaseMissing(
            'products',
            [
                'id' => $product->id,
            ],
        );
    }

    public function test_product_category_assignments_are_synced_on_update(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product =
            Product::factory()->create();

        $oldCategory =
            Category::factory()->create();

        $newCategory =
            Category::factory()->create();

        $product->categories()->attach(
            $oldCategory,
        );

        $this->actingAs(
            $admin,
            'admin',
        )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                [
                    'brand_id' => null,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'short_description' => null,
                    'description' => null,
                    'status' => ProductStatus::Draft->value,
                    'is_featured' => false,
                    'position' => 0,
                    'published_at' => null,
                    'meta_title' => null,
                    'meta_description' => null,
                    'category_ids' => [
                        $newCategory->id,
                    ],
                ],
            )
            ->assertRedirect();

        $product->refresh();

        $this->assertTrue(
            $product->categories()
                ->whereKey(
                    $newCategory->id,
                )
                ->exists(),
        );

        $this->assertFalse(
            $product->categories()
                ->whereKey(
                    $oldCategory->id,
                )
                ->exists(),
        );
    }

    public function test_invalid_product_status_is_rejected(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->post(
                route(
                    'admin.products.store',
                ),
                [
                    'name' => 'Invalid Product',
                    'status' => 'evil-status',
                    'is_featured' => false,
                    'position' => 0,
                    'category_ids' => [],
                ],
            )
            ->assertSessionHasErrors([
                'status',
            ]);
    }

    public function test_future_publication_date_can_be_saved(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $futureDate = now()
            ->addDay()
            ->format('Y-m-d H:i:s');

        $this->actingAs(
            $admin,
            'admin',
        )
            ->post(
                route(
                    'admin.products.store',
                ),
                [
                    'name' => 'Scheduled Product',
                    'status' => ProductStatus::Published->value,
                    'is_featured' => false,
                    'position' => 0,
                    'published_at' => $futureDate,
                    'category_ids' => [],
                ],
            )
            ->assertRedirect();

        $product = Product::query()
            ->where(
                'name',
                'Scheduled Product',
            )
            ->firstOrFail();

        $this->assertFalse(
            $product->isPublished(),
        );
    }
}
