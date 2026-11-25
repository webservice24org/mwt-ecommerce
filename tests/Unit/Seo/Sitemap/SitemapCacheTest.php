<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Services\SitemapCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SitemapCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
    }

    public function test_remember_caches_value(): void
    {
        $cache =
            new SitemapCache;

        $calls = 0;

        $first =
            $cache->remember(
                'index',
                function () use (&$calls): string {
                    $calls++;

                    return 'first';
                },
            );

        $second =
            $cache->remember(
                'index',
                function () use (&$calls): string {
                    $calls++;

                    return 'second';
                },
            );

        self::assertSame(
            'first',
            $first,
        );

        self::assertSame(
            'first',
            $second,
        );

        self::assertSame(
            1,
            $calls,
        );
    }

    public function test_invalidation_advances_namespace(): void
    {
        $cache =
            new SitemapCache;

        $cache->remember(
            'index',
            static fn (): string => 'before',
        );

        $beforeVersion =
            $cache->version();

        $cache->invalidate();

        $afterVersion =
            $cache->version();

        self::assertSame(
            $beforeVersion + 1,
            $afterVersion,
        );

        self::assertSame(
            'after',
            $cache->remember(
                'index',
                static fn (): string => 'after',
            ),
        );
    }

    public function test_missing_values_are_not_cached(): void
    {
        $cache =
            new SitemapCache;

        $calls = 0;

        $first =
            $cache->rememberNotNull(
                'part:brands:9',
                function () use (&$calls): ?string {
                    $calls++;

                    return null;
                },
            );

        $second =
            $cache->rememberNotNull(
                'part:brands:9',
                function () use (&$calls): ?string {
                    $calls++;

                    return null;
                },
            );

        self::assertNull(
            $first,
        );

        self::assertNull(
            $second,
        );

        self::assertSame(
            2,
            $calls,
        );
    }
}
