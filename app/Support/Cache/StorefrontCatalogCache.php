<?php

declare(strict_types=1);

namespace App\Support\Cache;

use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class StorefrontCatalogCache
{
    private const VERSION_KEY = 'storefront:catalog:version';

    private const VERSION_LOCK_KEY =
        'storefront:catalog:version-lock';

    private const VERSION_LOCK_SECONDS = 10;

    private const VERSION_LOCK_WAIT_SECONDS = 5;

    public const DEFAULT_TTL_SECONDS = 300;

    /**
     * @template TCacheValue
     *
     * @param  Closure(): TCacheValue  $callback
     * @return TCacheValue
     */
    public function remember(
        string $key,
        Closure $callback,
        ?int $ttlSeconds = null,
    ): mixed {
        return $this->repository()->remember(
            $this->key($key),
            $ttlSeconds
                ?? self::DEFAULT_TTL_SECONDS,
            $callback,
        );
    }

    /**
     * @template TCacheValue
     *
     * @param  Closure(): (TCacheValue|null)  $callback
     * @return TCacheValue|null
     */
    public function rememberNotNull(
        string $key,
        Closure $callback,
        ?int $ttlSeconds = null,
    ): mixed {
        $cacheKey = $this->key($key);

        $cached = $this->repository()->get(
            $cacheKey,
        );

        if ($cached !== null) {
            /** @var TCacheValue $cached */
            return $cached;
        }

        $value = $callback();

        if ($value !== null) {
            $this->repository()->put(
                $cacheKey,
                $value,
                $ttlSeconds
                    ?? self::DEFAULT_TTL_SECONDS,
            );
        }

        return $value;
    }

    public function forget(
        string $key,
    ): bool {
        return $this->repository()->forget(
            $this->key($key),
        );
    }

    public function invalidate(): void
    {
        $this->withVersionLock(
            function (): void {
                $repository = $this->repository();

                $current = $this->readVersion(
                    $repository,
                );

                $repository->forever(
                    self::VERSION_KEY,
                    $current + 1,
                );
            },
        );
    }

    public function version(): int
    {
        $repository = $this->repository();

        $version = $repository->get(
            self::VERSION_KEY,
        );

        if (
            is_int($version)
            && $version >= 1
        ) {
            return $version;
        }

        return $this->withVersionLock(
            function () use (
                $repository,
            ): int {
                $version = $repository->get(
                    self::VERSION_KEY,
                );

                if (
                    is_int($version)
                    && $version >= 1
                ) {
                    return $version;
                }

                $repository->forever(
                    self::VERSION_KEY,
                    1,
                );

                return 1;
            },
        );
    }

    public function key(
        string $key,
    ): string {
        return sprintf(
            'storefront:catalog:v%d:%s',
            $this->version(),
            ltrim($key, ':'),
        );
    }

    /**
     * @template TLockResult
     *
     * @param  Closure(): TLockResult  $callback
     * @return TLockResult
     */
    private function withVersionLock(
        Closure $callback,
    ): mixed {
        $store = $this->repository()->getStore();

        if (! $store instanceof LockProvider) {
            throw new RuntimeException(
                sprintf(
                    'The storefront cache store [%s] does not support atomic locks.',
                    $this->storeName(),
                ),
            );
        }

        return $store
            ->lock(
                self::VERSION_LOCK_KEY,
                self::VERSION_LOCK_SECONDS,
            )
            ->block(
                self::VERSION_LOCK_WAIT_SECONDS,
                $callback,
            );
    }

    private function readVersion(
        Repository $repository,
    ): int {
        $version = $repository->get(
            self::VERSION_KEY,
        );

        return is_int($version)
            && $version >= 1
                ? $version
                : 1;
    }

    private function repository(): Repository
    {
        return Cache::store(
            $this->storeName(),
        );
    }

    private function storeName(): string
    {
        $store = config(
            'cache.storefront_store',
            config('cache.default'),
        );

        return is_string($store)
            && $store !== ''
                ? $store
                : 'database';
    }
}
