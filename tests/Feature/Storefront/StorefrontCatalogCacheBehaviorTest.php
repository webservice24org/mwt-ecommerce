<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\Data\UpdateProductData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class StorefrontCatalogCacheBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_remember_executes_callback_on_cache_miss(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $calls = 0;

        $value = $cache->remember(
            'behavior:miss',
            function () use (&$calls): string {
                $calls++;

                return 'cached-value';
            },
        );

        $this->assertSame(
            'cached-value',
            $value,
        );

        $this->assertSame(
            1,
            $calls,
        );
    }

    public function test_remember_does_not_execute_callback_on_cache_hit(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $calls = 0;

        $first = $cache->remember(
            'behavior:hit',
            function () use (&$calls): string {
                $calls++;

                return 'first-value';
            },
        );

        $second = $cache->remember(
            'behavior:hit',
            function () use (&$calls): string {
                $calls++;

                return 'second-value';
            },
        );

        $this->assertSame(
            'first-value',
            $first,
        );

        $this->assertSame(
            'first-value',
            $second,
        );

        $this->assertSame(
            1,
            $calls,
        );
    }

    public function test_remember_not_null_does_not_cache_null_values(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $calls = 0;

        $first = $cache->rememberNotNull(
            'behavior:not-null',
            function () use (&$calls): ?string {
                $calls++;

                return null;
            },
        );

        $second = $cache->rememberNotNull(
            'behavior:not-null',
            function () use (&$calls): ?string {
                $calls++;

                return null;
            },
        );

        $this->assertNull($first);
        $this->assertNull($second);

        $this->assertSame(
            2,
            $calls,
        );
    }

    public function test_remember_not_null_caches_non_null_values(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $calls = 0;

        $first = $cache->rememberNotNull(
            'behavior:not-null-hit',
            function () use (&$calls): ?string {
                $calls++;

                return 'public-value';
            },
        );

        $second = $cache->rememberNotNull(
            'behavior:not-null-hit',
            function () use (&$calls): ?string {
                $calls++;

                return 'changed-value';
            },
        );

        $this->assertSame(
            'public-value',
            $first,
        );

        $this->assertSame(
            'public-value',
            $second,
        );

        $this->assertSame(
            1,
            $calls,
        );
    }

    public function test_invalidation_increments_storefront_generation(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        $cache->invalidate();

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );
    }

    public function test_invalidation_changes_generated_cache_key(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->key(
            'products/example-product',
        );

        $cache->invalidate();

        $after = $cache->key(
            'products/example-product',
        );

        $this->assertNotSame(
            $before,
            $after,
        );

        $this->assertStringContainsString(
            ':v1:',
            $before,
        );

        $this->assertStringContainsString(
            ':v2:',
            $after,
        );
    }

    public function test_invalidation_makes_old_generation_unreachable(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $calls = 0;

        $first = $cache->remember(
            'behavior:generation',
            function () use (&$calls): string {
                $calls++;

                return 'generation-one';
            },
        );

        $cache->invalidate();

        $second = $cache->remember(
            'behavior:generation',
            function () use (&$calls): string {
                $calls++;

                return 'generation-two';
            },
        );

        $this->assertSame(
            'generation-one',
            $first,
        );

        $this->assertSame(
            'generation-two',
            $second,
        );

        $this->assertSame(
            2,
            $calls,
        );
    }

    public function test_old_generation_value_does_not_leak_into_new_generation(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $oldKey = $cache->key(
            'behavior:isolation',
        );

        $oldValue = $cache->remember(
            'behavior:isolation',
            static fn (): string => 'old-value',
        );

        $this->assertSame(
            'old-value',
            $oldValue,
        );

        $store = Cache::store(
            (string) config(
                'cache.storefront_store',
                config('cache.default'),
            ),
        );

        $this->assertSame(
            'old-value',
            $store->get($oldKey),
        );

        $cache->invalidate();

        $newKey = $cache->key(
            'behavior:isolation',
        );

        $this->assertNotSame(
            $oldKey,
            $newKey,
        );

        /*
        * Generation invalidation intentionally does not physically
        * delete the old cache entry. It becomes unreachable through
        * the new storefront cache generation and expires naturally
        * according to its TTL.
        */
        $this->assertSame(
            'old-value',
            $store->get($oldKey),
        );

        $this->assertNull(
            $store->get($newKey),
        );

        $newValue = $cache->remember(
            'behavior:isolation',
            static fn (): string => 'new-value',
        );

        $this->assertSame(
            'new-value',
            $newValue,
        );

        $this->assertSame(
            'new-value',
            $store->get($newKey),
        );

        /*
        * The old generation still physically exists until TTL expiry,
        * but it cannot leak into the current generation.
        */
        $this->assertSame(
            'old-value',
            $store->get($oldKey),
        );
    }

    public function test_successful_product_update_invalidates_cached_product_detail(): void
    {
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'slug' => 'cache-invalidation-product',
            'type' => ProductType::Simple,
            'sku' => 'CACHE-001',
            'price' => 10000,
            'compare_at_price' => 12000,
            'cost_price' => 7000,
            'status' => ProductStatus::Published,
            'is_featured' => false,
            'position' => 0,
            'published_at' => now()->subMinute(),
        ]);

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $first = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($first);

        $this->assertSame(
            'Original Product',
            $first->name,
        );

        $this->assertSame(
            10000,
            $first->pricing->price,
        );

        $versionBeforeUpdate = $cache->version();

        app(
            UpdateProductAction::class,
        )->execute(
            $product,
            new UpdateProductData(
                brandId: null,
                type: ProductType::Simple,
                sku: 'CACHE-001',
                price: 15000,
                compareAtPrice: 18000,
                costPrice: 9000,
                name: 'Updated Product',
                slug: 'cache-invalidation-product',
                shortDescription: 'Updated short description.',
                description: '<p>Updated description.</p>',
                status: ProductStatus::Published,
                isFeatured: true,
                position: 10,
                publishedAt: CarbonImmutable::now()->subMinute(),
                metaTitle: 'Updated Product Meta Title',
                metaDescription: 'Updated product meta description.',
                categoryIds: [],
            ),
        );

        $this->assertSame(
            $versionBeforeUpdate + 1,
            $cache->version(),
        );

        $second = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($second);

        $this->assertSame(
            'Updated Product',
            $second->name,
        );

        $this->assertSame(
            15000,
            $second->pricing->price,
        );

        $this->assertSame(
            18000,
            $second->pricing->compareAtPrice,
        );

        $this->assertSame(
            'Updated short description.',
            $second->shortDescription,
        );

        $this->assertSame(
            'Updated Product Meta Title',
            $second->metaTitle,
        );

        $this->assertNotSame(
            $first->toArray(),
            $second->toArray(),
        );
    }
}
