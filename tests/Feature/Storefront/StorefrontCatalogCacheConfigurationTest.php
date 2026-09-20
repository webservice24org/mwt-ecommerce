<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Tests\TestCase;

final class StorefrontCatalogCacheConfigurationTest extends TestCase
{
    public function test_storefront_cache_store_is_configured(): void
    {
        $store = config('cache.storefront_store');

        $this->assertIsString($store);
        $this->assertNotSame('', $store);

        $this->assertArrayHasKey(
            $store,
            config('cache.stores', []),
        );
    }

    public function test_redis_cache_store_uses_the_cache_redis_connection(): void
    {
        $redisStore = config('cache.stores.redis');

        $this->assertIsArray($redisStore);

        $this->assertSame(
            'redis',
            $redisStore['driver'] ?? null,
        );

        $this->assertSame(
            'cache',
            $redisStore['connection'] ?? null,
        );
    }

    public function test_redis_cache_connection_is_configured(): void
    {
        $redis = config('database.redis');

        $this->assertIsArray($redis);

        $this->assertArrayHasKey(
            'cache',
            $redis,
        );

        $this->assertSame(
            '1',
            (string) config(
                'database.redis.cache.database',
            ),
        );
    }

    public function test_redis_client_is_supported(): void
    {
        $client = config(
            'database.redis.client',
        );

        $this->assertContains(
            $client,
            [
                'phpredis',
                'predis',
            ],
        );
    }
}
