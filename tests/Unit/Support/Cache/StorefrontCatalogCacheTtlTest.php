<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Cache;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheTtl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class StorefrontCatalogCacheTtlTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_default_ttl_when_there_is_no_future_publication(): void
    {
        Carbon::setTestNow(
            '2026-09-20 10:00:00',
        );

        $ttl = app(
            StorefrontCatalogCacheTtl::class,
        )->seconds();

        $this->assertSame(
            StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
            $ttl,
        );
    }

    public function test_it_shortens_ttl_to_the_next_future_publication(): void
    {
        Carbon::setTestNow(
            '2026-09-20 10:00:00',
        );

        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addSeconds(90),
        ]);

        $ttl = app(
            StorefrontCatalogCacheTtl::class,
        )->seconds();

        $this->assertSame(
            90,
            $ttl,
        );
    }

    public function test_it_uses_the_nearest_future_publication(): void
    {
        Carbon::setTestNow(
            '2026-09-20 10:00:00',
        );

        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addSeconds(200),
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addSeconds(60),
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addSeconds(120),
        ]);

        $ttl = app(
            StorefrontCatalogCacheTtl::class,
        )->seconds();

        $this->assertSame(
            60,
            $ttl,
        );
    }

    public function test_draft_future_product_does_not_affect_ttl(): void
    {
        Carbon::setTestNow(
            '2026-09-20 10:00:00',
        );

        Product::factory()->create([
            'status' => ProductStatus::Draft,
            'published_at' => now()->addSeconds(30),
        ]);

        $ttl = app(
            StorefrontCatalogCacheTtl::class,
        )->seconds();

        $this->assertSame(
            StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
            $ttl,
        );
    }

    public function test_publication_beyond_default_window_uses_default_ttl(): void
    {
        Carbon::setTestNow(
            '2026-09-20 10:00:00',
        );

        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addSeconds(
                StorefrontCatalogCache::DEFAULT_TTL_SECONDS + 300,
            ),
        ]);

        $ttl = app(
            StorefrontCatalogCacheTtl::class,
        )->seconds();

        $this->assertSame(
            StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
            $ttl,
        );
    }
}
