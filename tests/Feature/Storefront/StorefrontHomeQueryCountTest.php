<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MeasuresDatabaseQueries;
use Tests\TestCase;

final class StorefrontHomeQueryCountTest extends TestCase
{
    use MeasuresDatabaseQueries;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Keep storefront cache isolated from the database so
         * cache implementation queries are not counted as catalog
         * SQL during these N+1 measurements.
         */
        config()->set(
            'cache.storefront_store',
            'array',
        );
    }

    public function test_homepage_query_count_does_not_grow_with_product_count(): void
    {
        $this->createPublishedProducts(
            count: 1,
            featured: true,
        );

        $small = $this->measureColdHomeQueries();

        /*
         * StorefrontHomeQuery limits both featured products and
         * new arrivals to 8. Seven additional products therefore
         * exercise a fully populated homepage collection.
         */
        $this->createPublishedProducts(
            count: 7,
            featured: true,
        );

        $large = $this->measureColdHomeQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Homepage query count grew from %d to %d when product count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_homepage_query_count_does_not_grow_with_variant_count(): void
    {
        $product = $this->createPublishedProduct(
            featured: true,
        );

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
            ]);

        $small = $this->measureColdHomeQueries();

        ProductVariant::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => false,
            ]);

        $large = $this->measureColdHomeQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Homepage query count grew from %d to %d when active variant count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_homepage_query_count_does_not_grow_with_product_images(): void
    {
        $product = $this->createPublishedProduct(
            featured: true,
        );

        ProductImage::factory()
            ->for($product)
            ->create([
                'is_primary' => true,
                'position' => 0,
            ]);

        $small = $this->measureColdHomeQueries();

        ProductImage::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_primary' => false,
            ]);

        $large = $this->measureColdHomeQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Homepage query count grew from %d to %d when gallery image count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_homepage_query_count_does_not_grow_with_category_count(): void
    {
        Category::factory()->create([
            'parent_id' => null,
            'is_active' => true,
        ]);

        $small = $this->measureColdHomeQueries();

        /*
         * Homepage currently limits its top-level category
         * showcase to six categories.
         */
        Category::factory()
            ->count(5)
            ->create([
                'parent_id' => null,
                'is_active' => true,
            ]);

        $large = $this->measureColdHomeQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Homepage query count grew from %d to %d when category count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_homepage_query_count_does_not_grow_with_brand_reuse(): void
    {
        $brand = Brand::factory()->create([
            'is_active' => true,
        ]);

        $this->createPublishedProduct(
            featured: true,
            brand: $brand,
        );

        $small = $this->measureColdHomeQueries();

        $this->createPublishedProducts(
            count: 7,
            featured: true,
            brand: $brand,
        );

        $large = $this->measureColdHomeQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Homepage query count grew from %d to %d when branded products increased.',
                $small,
                $large,
            ),
        );
    }

    private function measureColdHomeQueries(): int
    {
        /*
         * Generation invalidation is preferable to Cache::flush().
         * It makes the next homepage lookup cold without flushing
         * unrelated application cache data.
         *
         * Do this BEFORE query logging starts so the invalidation
         * mechanism itself is not part of the catalog query budget.
         */
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $measurement = $this->measureDatabaseQueries(
            fn () => app(
                StorefrontHomeQuery::class,
            )->get(),
        );

        return $measurement['count'];
    }

    private function createPublishedProduct(
        bool $featured = false,
        ?Brand $brand = null,
    ): Product {
        $brand ??= Brand::factory()->create([
            'is_active' => true,
        ]);

        return Product::factory()
            ->for($brand)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'is_featured' => $featured,
            ]);
    }

    private function createPublishedProducts(
        int $count,
        bool $featured = false,
        ?Brand $brand = null,
    ): void {
        $brand ??= Brand::factory()->create([
            'is_active' => true,
        ]);

        Product::factory()
            ->count($count)
            ->for($brand)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'is_featured' => $featured,
            ]);
    }
}
