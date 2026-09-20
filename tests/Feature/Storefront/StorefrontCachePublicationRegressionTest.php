<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\Data\UpdateProductData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Category;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontCachePublicationRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );
    }

    public function test_cached_public_product_disappears_after_becoming_draft(): void
    {
        $product = $this->createPublicProduct();

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $cached = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($cached);

        $versionBefore = app(
            StorefrontCatalogCache::class,
        )->version();

        $updated = $this->updateProduct(
            $product,
            status: ProductStatus::Draft,
        );

        $versionAfter = app(
            StorefrontCatalogCache::class,
        )->version();

        $this->assertGreaterThan(
            $versionBefore,
            $versionAfter,
        );

        $this->assertNull(
            $query->findBySlug(
                $updated->slug,
            ),
        );

        $this->get(
            '/products/'.$updated->slug,
        )->assertNotFound();
    }

    public function test_cached_public_product_disappears_after_becoming_archived(): void
    {
        $product = $this->createPublicProduct();

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $this->assertNotNull(
            $query->findBySlug(
                $product->slug,
            ),
        );

        $updated = $this->updateProduct(
            $product,
            status: ProductStatus::Archived,
        );

        $this->assertNull(
            $query->findBySlug(
                $updated->slug,
            ),
        );
    }

    public function test_cached_public_product_disappears_after_being_rescheduled_into_future(): void
    {
        $product = $this->createPublicProduct();

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $this->assertNotNull(
            $query->findBySlug(
                $product->slug,
            ),
        );

        $updated = $this->updateProduct(
            $product,
            status: ProductStatus::Published,
            publishedAt: CarbonImmutable::now()
                ->addHour(),
        );

        $this->assertNull(
            $query->findBySlug(
                $updated->slug,
            ),
        );

        $this->get(
            '/products/'.$updated->slug,
        )->assertNotFound();
    }

    public function test_cached_home_removes_product_after_product_becomes_draft(): void
    {
        $product = $this->createPublicProduct(
            featured: true,
        );

        $homeQuery = app(
            StorefrontHomeQuery::class,
        );

        $before = $homeQuery->get();

        $this->assertTrue(
            $this->homeContainsProduct(
                $before->toArray(),
                $product->id,
            ),
        );

        $this->updateProduct(
            $product,
            status: ProductStatus::Draft,
        );

        $after = $homeQuery->get();

        $this->assertFalse(
            $this->homeContainsProduct(
                $after->toArray(),
                $product->id,
            ),
        );
    }

    public function test_cached_home_removes_product_after_product_is_rescheduled_into_future(): void
    {
        $product = $this->createPublicProduct(
            featured: true,
        );

        $homeQuery = app(
            StorefrontHomeQuery::class,
        );

        $before = $homeQuery->get();

        $this->assertTrue(
            $this->homeContainsProduct(
                $before->toArray(),
                $product->id,
            ),
        );

        $this->updateProduct(
            $product,
            status: ProductStatus::Published,
            publishedAt: CarbonImmutable::now()
                ->addDay(),
        );

        $after = $homeQuery->get();

        $this->assertFalse(
            $this->homeContainsProduct(
                $after->toArray(),
                $product->id,
            ),
        );
    }

    public function test_draft_product_becomes_public_after_successful_publish_update(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Draft,
            featured: true,
        );

        $detailQuery = app(
            StorefrontProductDetailQuery::class,
        );

        $this->assertNull(
            $detailQuery->findBySlug(
                $product->slug,
            ),
        );

        $updated = $this->updateProduct(
            $product,
            status: ProductStatus::Published,
            publishedAt: null,
        );

        $detail = $detailQuery->findBySlug(
            $updated->slug,
        );

        $this->assertNotNull($detail);
        $this->assertSame(
            $updated->id,
            $detail->id,
        );

        $this->get(
            '/products/'.$updated->slug,
        )->assertOk();
    }

    public function test_publishing_product_invalidates_cached_home_and_makes_product_visible(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Draft,
            featured: true,
        );

        $homeQuery = app(
            StorefrontHomeQuery::class,
        );

        $before = $homeQuery->get();

        $this->assertFalse(
            $this->homeContainsProduct(
                $before->toArray(),
                $product->id,
            ),
        );

        $updated = $this->updateProduct(
            $product,
            status: ProductStatus::Published,
            publishedAt: null,
        );

        $after = $homeQuery->get();

        $this->assertTrue(
            $this->homeContainsProduct(
                $after->toArray(),
                $updated->id,
            ),
        );
    }

    public function test_successful_update_changes_cache_generation_exactly_once(): void
    {
        $product = $this->createPublicProduct();

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        $this->updateProduct(
            $product,
            status: ProductStatus::Draft,
        );

        $after = $cache->version();

        $this->assertSame(
            $before + 1,
            $after,
        );
    }

    public function test_failed_product_update_does_not_change_cache_generation(): void
    {
        $product = $this->createPublicProduct();

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        try {
            $this->updateProduct(
                $product,
                type: ProductType::Simple,
                price: null,
            );

            $this->fail(
                'Expected invalid simple-product pricing to reject the update.',
            );
        } catch (\Throwable $exception) {
            $this->assertSame(
                $before,
                $cache->version(),
            );

            $product->refresh();

            $this->assertSame(
                ProductStatus::Published,
                $product->status,
            );

            $this->assertNotNull(
                app(
                    StorefrontProductDetailQuery::class,
                )->findBySlug(
                    $product->slug,
                ),
            );

            $this->assertNotSame(
                '',
                $exception->getMessage(),
            );
        }
    }

    public function test_slug_change_does_not_leave_old_cached_detail_publicly_reachable(): void
    {
        $product = $this->createPublicProduct();

        $detailQuery = app(
            StorefrontProductDetailQuery::class,
        );

        $oldSlug = $product->slug;

        $this->assertNotNull(
            $detailQuery->findBySlug(
                $oldSlug,
            ),
        );

        $updated = $this->updateProduct(
            $product,
            slug: 'updated-public-product-slug',
        );

        $this->assertSame(
            'updated-public-product-slug',
            $updated->slug,
        );

        $this->assertNull(
            $detailQuery->findBySlug(
                $oldSlug,
            ),
        );

        $this->assertNotNull(
            $detailQuery->findBySlug(
                $updated->slug,
            ),
        );

        $this->get(
            '/products/'.$oldSlug,
        )->assertNotFound();

        $this->get(
            '/products/'.$updated->slug,
        )->assertOk();
    }

    private function createPublicProduct(
        bool $featured = true,
    ): Product {
        return $this->createProduct(
            status: ProductStatus::Published,
            featured: $featured,
            publishedAt: CarbonImmutable::now()
                ->subMinute(),
        );
    }

    private function createProduct(
        ProductStatus $status,
        bool $featured,
        ?CarbonImmutable $publishedAt = null,
    ): Product {
        $product = Product::factory()->create([
            'type' => ProductType::Simple,
            'sku' => null,
            'price' => 10000,
            'compare_at_price' => 12000,
            'cost_price' => 7000,
            'status' => $status,
            'is_featured' => $featured,
            'published_at' => $publishedAt,
        ]);

        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product->categories()->attach(
            $category->id,
        );

        return $product;
    }

    private function updateProduct(
        Product $product,
        ?ProductStatus $status = null,
        ?ProductType $type = null,
        ?int $price = 10000,
        ?CarbonImmutable $publishedAt = null,
        ?string $slug = null,
    ): Product {
        $product->refresh();

        return app(
            UpdateProductAction::class,
        )->execute(
            $product,
            new UpdateProductData(
                brandId: $product->brand_id,
                type: $type ?? $product->type,
                sku: $product->sku,
                price: $price,
                compareAtPrice: $product->compare_at_price,
                costPrice: $product->cost_price,
                name: $product->name,
                slug: $slug ?? $product->slug,
                shortDescription: $product->short_description,
                description: $product->description,
                status: $status ?? $product->status,
                isFeatured: $product->is_featured,
                position: $product->position,
                publishedAt: $publishedAt,
                metaTitle: $product->meta_title,
                metaDescription: $product->meta_description,
                categoryIds: $product
                    ->categories()
                    ->pluck('categories.id')
                    ->map(
                        static fn ($id): int => (int) $id,
                    )
                    ->all(),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $home
     */
    private function homeContainsProduct(
        array $home,
        int $productId,
    ): bool {
        return collect(
            $home['featured_products'] ?? [],
        )
            ->merge(
                $home['new_arrivals'] ?? [],
            )
            ->contains(
                fn ($item): bool => (int) $item['id']
                    === $productId,
            );
    }
}
