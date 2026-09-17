<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Enums\ProductVideoType;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
                    'type' => ProductType::Simple->value,
                    'sku' => 'MACBOOK-PRO-001',
                    'price' => 199900,
                    'compare_at_price' => 219900,
                    'cost_price' => 150000,
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

        $this->assertSame(
            ProductType::Simple,
            $product->type,
        );

        $this->assertSame(
            'MACBOOK-PRO-001',
            $product->sku,
        );

        $this->assertSame(
            199900,
            $product->price,
        );

        $this->assertSame(
            219900,
            $product->compare_at_price,
        );

        $this->assertSame(
            150000,
            $product->cost_price,
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
                    'type' => ProductType::Simple->value,
                    'sku' => 'UPDATED-PRODUCT-001',
                    'price' => 25000,
                    'compare_at_price' => 30000,
                    'cost_price' => 18000,
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
                'type' => ProductType::Simple->value,
                'sku' => 'UPDATED-PRODUCT-001',
                'price' => 25000,
                'compare_at_price' => 30000,
                'cost_price' => 18000,
                'name' => 'Updated Product',
            ],
        );
    }

    public function test_editor_cannot_delete_product(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $product = Product::factory()->create();

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
                    'type' => ProductType::Simple->value,
                    'sku' => $product->sku,
                    'price' => $product->price ?? 1000,
                    'compare_at_price' => null,
                    'cost_price' => null,
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
                    'type' => ProductType::Simple->value,
                    'sku' => 'SCHEDULED-PRODUCT-001',
                    'price' => 10000,
                    'compare_at_price' => null,
                    'cost_price' => null,
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

    public function test_invalid_category_id_is_rejected(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route('admin.products.store'),
                [
                    'name' => 'Invalid Category Product',
                    'status' => ProductStatus::Draft->value,
                    'is_featured' => false,
                    'position' => 0,
                    'category_ids' => [
                        999999,
                    ],
                ],
            )
            ->assertSessionHasErrors(
                'category_ids.0',
            );

        $this->assertDatabaseMissing(
            'products',
            [
                'name' => 'Invalid Category Product',
            ],
        );
    }

    public function test_duplicate_category_ids_are_rejected(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $category = Category::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route('admin.products.store'),
                [
                    'name' => 'Duplicate Category Product',
                    'status' => ProductStatus::Draft->value,
                    'is_featured' => false,
                    'position' => 0,
                    'category_ids' => [
                        $category->id,
                        $category->id,
                    ],
                ],
            )
            ->assertSessionHasErrors(
                'category_ids.0',
            );
    }

    public function test_deleting_product_removes_physical_media_files(): void
    {
        Storage::fake('public');

        $manager = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $product = Product::factory()->create();

        $imagePath =
            "catalog/products/{$product->id}/images/product.jpg";

        $videoPath =
            "catalog/products/{$product->id}/videos/product.mp4";

        Storage::disk('public')->put(
            $imagePath,
            'fake-image-content',
        );

        Storage::disk('public')->put(
            $videoPath,
            'fake-video-content',
        );

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => $imagePath,
            'is_primary' => true,
        ]);

        ProductVideo::query()->create([
            'product_id' => $product->id,
            'type' => ProductVideoType::Upload,
            'path' => $videoPath,
            'url' => null,
            'original_name' => 'product.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 1024,
            'title' => 'Product Video',
        ]);

        $this->assertTrue(
            Storage::disk('public')->exists(
                $imagePath,
            ),
        );

        $this->assertTrue(
            Storage::disk('public')->exists(
                $videoPath,
            ),
        );

        $this
            ->actingAs($manager, 'admin')
            ->delete(
                route(
                    'admin.products.destroy',
                    $product,
                ),
            )
            ->assertRedirect(
                route('admin.products.index'),
            );

        $this->assertDatabaseMissing(
            'products',
            [
                'id' => $product->id,
            ],
        );

        $this->assertDatabaseMissing(
            'product_images',
            [
                'product_id' => $product->id,
            ],
        );

        $this->assertDatabaseMissing(
            'product_videos',
            [
                'product_id' => $product->id,
            ],
        );

        $this->assertFalse(
            Storage::disk('public')->exists(
                $imagePath,
            ),
        );

        $this->assertFalse(
            Storage::disk('public')->exists(
                $videoPath,
            ),
        );
    }
}
