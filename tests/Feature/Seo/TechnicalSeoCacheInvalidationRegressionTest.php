<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TechnicalSeoCacheInvalidationRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_catalog_cache_invalidation_rotates_namespace_and_hides_stale_values(): void
    {
        $cache = app(StorefrontCatalogCache::class);

        $beforeVersion = $cache->version();
        $beforeKey = $cache->key('seo:q3:probe');

        self::assertSame(
            'before',
            $cache->remember(
                'seo:q3:probe',
                static fn (): string => 'before',
            ),
        );

        $cache->invalidate();

        $afterVersion = $cache->version();
        $afterKey = $cache->key('seo:q3:probe');

        self::assertGreaterThan(
            $beforeVersion,
            $afterVersion,
        );

        self::assertNotSame(
            $beforeKey,
            $afterKey,
        );

        self::assertSame(
            'after',
            $cache->remember(
                'seo:q3:probe',
                static fn (): string => 'after',
            ),
        );
    }

    public function test_catalog_cache_invalidation_is_safe_when_version_key_does_not_exist(): void
    {
        $cache = app(StorefrontCatalogCache::class);

        Cache::flush();

        $cache->invalidate();

        self::assertGreaterThanOrEqual(
            1,
            $cache->version(),
        );

        self::assertSame(
            'fresh',
            $cache->remember(
                'seo:q3:first-invalidation',
                static fn (): string => 'fresh',
            ),
        );
    }
}
