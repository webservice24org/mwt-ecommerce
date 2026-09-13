<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Actions\CreateProductAction;
use App\Domain\Catalog\Actions\DeleteProductAction;
use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\Data\CreateProductData;
use App\Domain\Catalog\Data\UpdateProductData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\ProductIndexQuery;
use App\Domain\Catalog\Queries\PublishedProductQuery;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_through_action(): void
    {
        $brand = Brand::factory()->create();

        $categories = Category::factory()
            ->count(2)
            ->create();

        $product = app(
            CreateProductAction::class,
        )->execute(
            new CreateProductData(
                brandId: $brand->id,
                name: 'Test Product',
                slug: null,
                shortDescription: 'Short description',
                description: 'Full description',
                status: ProductStatus::Draft,
                isFeatured: false,
                position: 1,
                publishedAt: null,
                metaTitle: 'Test SEO Title',
                metaDescription: 'Test SEO description.',
                categoryIds: $categories
                    ->modelKeys(),
            ),
        );

        $this->assertSame(
            'test-product',
            $product->slug,
        );

        $this->assertSame(
            $brand->id,
            $product->brand_id,
        );

        $this->assertCount(
            2,
            $product->categories,
        );
    }

    public function test_duplicate_product_slugs_are_made_unique(): void
    {
        Product::factory()->create([
            'slug' => 'test-product',
        ]);

        $product = app(
            CreateProductAction::class,
        )->execute(
            new CreateProductData(
                brandId: null,
                name: 'Test Product',
                slug: null,
                shortDescription: null,
                description: null,
                status: ProductStatus::Draft,
                isFeatured: false,
                position: 0,
                publishedAt: null,
                metaTitle: null,
                metaDescription: null,
            ),
        );

        $this->assertSame(
            'test-product-2',
            $product->slug,
        );
    }

    public function test_product_can_be_updated_and_categories_synced(): void
    {
        $product = Product::factory()->create();

        $oldCategory =
            Category::factory()->create();

        $newCategory =
            Category::factory()->create();

        $product->categories()->attach(
            $oldCategory,
        );

        $updated = app(
            UpdateProductAction::class,
        )->execute(
            $product,
            new UpdateProductData(
                brandId: null,
                name: 'Updated Product',
                slug: null,
                shortDescription: null,
                description: null,
                status: ProductStatus::Published,
                isFeatured: true,
                position: 5,
                publishedAt: CarbonImmutable::now(),
                metaTitle: null,
                metaDescription: null,
                categoryIds: [
                    $newCategory->id,
                ],
            ),
        );

        $this->assertSame(
            'Updated Product',
            $updated->name,
        );

        $this->assertSame(
            ProductStatus::Published,
            $updated->status,
        );

        $this->assertTrue(
            $updated->is_featured,
        );

        $this->assertTrue(
            $updated->categories->contains(
                $newCategory,
            ),
        );

        $this->assertFalse(
            $updated->categories->contains(
                $oldCategory,
            ),
        );
    }

    public function test_product_can_be_deleted_through_action(): void
    {
        $product =
            Product::factory()->create();

        app(
            DeleteProductAction::class,
        )->execute($product);

        $this->assertDatabaseMissing(
            'products',
            [
                'id' => $product->id,
            ],
        );
    }

    public function test_admin_index_query_can_filter_by_status(): void
    {
        Product::factory()->create([
            'status' => ProductStatus::Draft,
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Published,
        ]);

        $products = app(
            ProductIndexQuery::class,
        )->paginate(
            status: ProductStatus::Published,
        );

        $this->assertCount(
            1,
            $products->items(),
        );

        $this->assertSame(
            ProductStatus::Published,
            $products->items()[0]->status,
        );
    }

    public function test_published_query_excludes_future_products(): void
    {
        Product::factory()->create([
            'slug' => 'available-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'slug' => 'future-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $query = app(
            PublishedProductQuery::class,
        );

        $this->assertNotNull(
            $query->findBySlug(
                'available-product',
            ),
        );

        $this->assertNull(
            $query->findBySlug(
                'future-product',
            ),
        );
    }

    public function test_published_query_excludes_drafts(): void
    {
        Product::factory()->create([
            'slug' => 'draft-product',
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);

        $this->assertNull(
            app(
                PublishedProductQuery::class,
            )->findBySlug(
                'draft-product',
            ),
        );
    }
}
