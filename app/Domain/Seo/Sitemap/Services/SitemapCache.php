<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

final class SitemapCache
{
    private const VERSION_KEY =
        'seo:sitemap:version';

    public const DEFAULT_TTL_SECONDS =
        3600;

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
        return $this
            ->repository()
            ->remember(
                $this->key(
                    $key,
                ),
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
        $cacheKey =
            $this->key(
                $key,
            );

        $cached =
            $this
                ->repository()
                ->get(
                    $cacheKey,
                );

        if ($cached !== null) {
            /** @var TCacheValue $cached */
            return $cached;
        }

        $value =
            $callback();

        if ($value !== null) {
            $this
                ->repository()
                ->put(
                    $cacheKey,
                    $value,
                    $ttlSeconds
                    ?? self::DEFAULT_TTL_SECONDS,
                );
        }

        return $value;
    }

    public function invalidate(): void
    {
        $repository =
            $this->repository();

        if (
            ! $repository->has(
                self::VERSION_KEY,
            )
        ) {
            $repository->forever(
                self::VERSION_KEY,
                2,
            );

            return;
        }

        $repository->increment(
            self::VERSION_KEY,
        );
    }

    public function version(): int
    {
        $repository =
            $this->repository();

        $version =
            $repository->get(
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
    }

    public function key(
        string $key,
    ): string {
        return sprintf(
            'seo:sitemap:v%d:%s',
            $this->version(),
            ltrim(
                $key,
                ':',
            ),
        );
    }

    private function repository(): Repository
    {
        return Cache::store();
    }
}
