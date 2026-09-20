<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Brand;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheTtl;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class StorefrontPublicationCacheCorrectnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-09-20 12:00:00',
            ),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_default_cache_ttl_is_used_when_no_future_publication_exists(): void
    {
        $this->assertSame(
            StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
            app(StorefrontCatalogCacheTtl::class)->seconds(),
        );
    }

    public function test_ttl_is_shortened_to_nearest_future_publication(): void
    {
        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addSeconds(120),
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addSeconds(240),
        ]);

        $this->assertSame(
            120,
            app(StorefrontCatalogCacheTtl::class)->seconds(),
        );
    }

    public function test_ttl_never_exceeds_default_storefront_ttl(): void
    {
        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addMinutes(10),
        ]);

        $this->assertSame(
            StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
            app(StorefrontCatalogCacheTtl::class)->seconds(),
        );
    }

    public function test_draft_and_archived_future_products_do_not_shorten_ttl(): void
    {
        Product::factory()->create([
            'status' => ProductStatus::Draft,
            'published_at' => now()->addSeconds(30),
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Archived,
            'published_at' => now()->addSeconds(60),
        ]);

        $this->assertSame(
            StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
            app(StorefrontCatalogCacheTtl::class)->seconds(),
        );
    }

    public function test_future_product_detail_is_not_negatively_cached(): void
    {
        $product = Product::factory()->create([
            'slug' => 'scheduled-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addMinute(),
        ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $this->assertNull(
            $query->findBySlug($product->slug),
        );

        CarbonImmutable::setTestNow(
            now()->addMinute(),
        );

        $result = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($result);

        $this->assertSame(
            $product->id,
            $result->id,
        );
    }

    public function test_homepage_cache_expires_when_scheduled_product_becomes_public(): void
    {
        Product::factory()->create([
            'name' => 'Existing Product',
            'slug' => 'existing-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $scheduled = Product::factory()->create([
            'name' => 'Scheduled Product',
            'slug' => 'scheduled-home-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addMinute(),
        ]);

        $query = app(StorefrontHomeQuery::class);

        $before = $query->get();

        $this->assertFalse(
            collect($before->newArrivals)->contains(
                static fn ($product): bool => $product->id === $scheduled->id,
            ),
        );

        CarbonImmutable::setTestNow(
            now()->addSeconds(61),
        );

        $after = $query->get();

        $this->assertTrue(
            collect($after->newArrivals)->contains(
                static fn ($product): bool => $product->id === $scheduled->id,
            ),
        );
    }

    public function test_filter_options_cache_expires_when_scheduled_product_becomes_public(): void
    {
        $existingBrand = Brand::factory()->create([
            'name' => 'Existing Brand',
            'slug' => 'existing-brand',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'brand_id' => $existingBrand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $scheduledBrand = Brand::factory()->create([
            'name' => 'Scheduled Brand',
            'slug' => 'scheduled-brand',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'brand_id' => $scheduledBrand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->addMinute(),
        ]);

        $query = app(
            StorefrontFilterOptionsQuery::class,
        );

        $before = $query->get();

        $this->assertFalse(
            collect($before->brands)->contains(
                static fn ($brand): bool => $brand->slug === 'scheduled-brand',
            ),
        );

        CarbonImmutable::setTestNow(
            now()->addSeconds(61),
        );

        $after = $query->get();

        $this->assertTrue(
            collect($after->brands)->contains(
                static fn ($brand): bool => $brand->slug === 'scheduled-brand',
            ),
        );
    }

    public function test_draft_product_with_past_publication_time_never_becomes_public(): void
    {
        $product = Product::factory()->create([
            'slug' => 'draft-product',
            'status' => ProductStatus::Draft,
            'published_at' => now()->subHour(),
        ]);

        $result = app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );

        $this->assertNull($result);
    }

    public function test_archived_product_with_past_publication_time_never_becomes_public(): void
    {
        $product = Product::factory()->create([
            'slug' => 'archived-product',
            'status' => ProductStatus::Archived,
            'published_at' => now()->subHour(),
        ]);

        $result = app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );

        $this->assertNull($result);
    }
}
