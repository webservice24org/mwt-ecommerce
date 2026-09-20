<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontCatalogCacheQualityGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_cache_is_generation_isolated(): void
    {
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'slug' => 'quality-gate-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $first = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($first);
        $this->assertSame(
            'Original Product',
            $first->name,
        );

        Product::query()
            ->whereKey($product->id)
            ->update([
                'name' => 'Database Changed Product',
            ]);

        /*
         * Direct database changes deliberately bypass domain
         * invalidation, so the current cache generation must
         * continue returning the cached public DTO.
         */
        $cached = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($cached);
        $this->assertSame(
            'Original Product',
            $cached->name,
        );

        $before = $cache->version();

        $cache->invalidate();

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        /*
         * The new generation must no longer see the old
         * generation's cached payload.
         */
        $fresh = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($fresh);
        $this->assertSame(
            'Database Changed Product',
            $fresh->name,
        );
    }

    public function test_unpublished_products_never_enter_public_product_detail_cache(): void
    {
        $draft = Product::factory()->create([
            'slug' => 'quality-gate-draft',
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);

        $archived = Product::factory()->create([
            'slug' => 'quality-gate-archived',
            'status' => ProductStatus::Archived,
            'published_at' => now()->subDay(),
        ]);

        $future = Product::factory()->create([
            'slug' => 'quality-gate-future',
            'status' => ProductStatus::Published,
            'published_at' => now()->addHour(),
        ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $this->assertNull(
            $query->findBySlug($draft->slug),
        );

        $this->assertNull(
            $query->findBySlug($archived->slug),
        );

        $this->assertNull(
            $query->findBySlug($future->slug),
        );
    }

    public function test_missing_product_detail_is_not_negatively_cached(): void
    {
        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $this->assertNull(
            $query->findBySlug(
                'product-created-later',
            ),
        );

        Product::factory()->create([
            'name' => 'Created Later',
            'slug' => 'product-created-later',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        /*
         * No invalidation is performed here deliberately.
         *
         * A previous null result must not prevent the newly
         * created public product from becoming visible.
         */
        $product = $query->findBySlug(
            'product-created-later',
        );

        $this->assertNotNull($product);

        $this->assertSame(
            'Created Later',
            $product->name,
        );
    }

    public function test_public_product_detail_payload_does_not_expose_commercial_internal_fields(): void
    {
        $product = Product::factory()->create([
            'slug' => 'quality-gate-security',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'cost_price' => 5000,
        ]);

        $detail = app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($detail);

        $payload = $detail->toArray();

        $this->assertArrayNotHasKey(
            'cost_price',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'created_at',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'updated_at',
            $payload,
        );
    }
}
