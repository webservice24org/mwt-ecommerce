<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Cache;

use App\Support\Cache\StorefrontCatalogCache;
use Tests\TestCase;

final class StorefrontCatalogCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );
    }

    public function test_it_remembers_values_inside_the_current_catalog_version(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $calls = 0;

        $first = $cache->remember(
            'product:test-product',
            function () use (&$calls): string {
                $calls++;

                return 'cached-value';
            },
        );

        $second = $cache->remember(
            'product:test-product',
            function () use (&$calls): string {
                $calls++;

                return 'different-value';
            },
        );

        $this->assertSame(
            'cached-value',
            $first,
        );

        $this->assertSame(
            'cached-value',
            $second,
        );

        $this->assertSame(
            1,
            $calls,
        );
    }

    public function test_invalidation_advances_the_catalog_version(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        $cache->invalidate();

        $after = $cache->version();

        $this->assertSame(
            $before + 1,
            $after,
        );
    }

    public function test_invalidation_makes_previous_cached_values_unreachable(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $cache->remember(
            'product:test-product',
            static fn (): string => 'old-value',
        );

        $cache->invalidate();

        $value = $cache->remember(
            'product:test-product',
            static fn (): string => 'new-value',
        );

        $this->assertSame(
            'new-value',
            $value,
        );
    }
}
