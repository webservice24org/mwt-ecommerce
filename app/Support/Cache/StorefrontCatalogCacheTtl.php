<?php

declare(strict_types=1);

namespace App\Support\Cache;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use Carbon\CarbonImmutable;

final class StorefrontCatalogCacheTtl
{
    private const MINIMUM_SECONDS = 1;

    public function seconds(): int
    {
        $nextPublication = Product::query()
            ->where(
                'status',
                ProductStatus::Published->value,
            )
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->orderBy('published_at')
            ->value('published_at');

        if ($nextPublication === null) {
            return StorefrontCatalogCache::DEFAULT_TTL_SECONDS;
        }

        $publicationAt = CarbonImmutable::parse(
            (string) $nextPublication,
        );

        $seconds = now()->diffInSeconds(
            $publicationAt,
            false,
        );

        if ($seconds <= 0) {
            return self::MINIMUM_SECONDS;
        }

        return max(
            self::MINIMUM_SECONDS,
            min(
                StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
                (int) ceil($seconds),
            ),
        );
    }
}
