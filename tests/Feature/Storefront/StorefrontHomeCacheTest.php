<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Models\Category;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class StorefrontHomeCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_home_data_is_cached(): void
    {
        Product::factory()->create([
            'name' => 'Original Product',
            'slug' => 'original-product',
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'is_featured' => true,
        ]);

        $query = app(
            StorefrontHomeQuery::class,
        );

        $first = $query->get();

        Product::factory()->create([
            'name' => 'New Database Product',
            'slug' => 'new-database-product',
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'is_featured' => true,
        ]);

        $second = $query->get();

        $this->assertCount(
            1,
            $first->featuredProducts,
        );

        $this->assertCount(
            1,
            $second->featuredProducts,
        );

        $this->assertSame(
            'Original Product',
            $second->featuredProducts[0]->name,
        );
    }

    public function test_catalog_cache_invalidation_refreshes_home_data(): void
    {
        Product::factory()->create([
            'name' => 'Original Product',
            'slug' => 'original-product',
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'is_featured' => true,
        ]);

        $query = app(
            StorefrontHomeQuery::class,
        );

        $first = $query->get();

        Product::factory()->create([
            'name' => 'New Product',
            'slug' => 'new-product',
            'status' => 'published',
            'published_at' => now(),
            'is_featured' => true,
        ]);

        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $second = $query->get();

        $this->assertCount(
            1,
            $first->featuredProducts,
        );

        $this->assertCount(
            2,
            $second->featuredProducts,
        );
    }

    public function test_home_categories_are_cached(): void
    {
        Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $query = app(
            StorefrontHomeQuery::class,
        );

        $first = $query->get();

        Category::factory()->create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $second = $query->get();

        $this->assertCount(
            1,
            $first->categories,
        );

        $this->assertCount(
            1,
            $second->categories,
        );

        $this->assertSame(
            'Phones',
            $second->categories[0]->name,
        );
    }

    public function test_cache_invalidation_refreshes_home_categories(): void
    {
        Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $query = app(
            StorefrontHomeQuery::class,
        );

        $query->get();

        Category::factory()->create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'parent_id' => null,
            'is_active' => true,
        ]);

        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $fresh = $query->get();

        $this->assertCount(
            2,
            $fresh->categories,
        );
    }
}
