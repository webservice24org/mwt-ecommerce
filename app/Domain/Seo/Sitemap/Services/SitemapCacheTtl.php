<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Models\Page;
use App\Models\Product;
use Carbon\CarbonImmutable;

final class SitemapCacheTtl
{
    private const MINIMUM_SECONDS = 1;

    public function seconds(): int
    {
        $publicationTimes = [
            $this->nextProductPublication(),
            $this->nextPagePublication(),
        ];

        $nextPublication =
            collect(
                $publicationTimes,
            )
                ->filter()
                ->sort()
                ->first();

        if (
            ! is_string(
                $nextPublication,
            )
        ) {
            return SitemapCache::DEFAULT_TTL_SECONDS;
        }

        $publicationAt =
            CarbonImmutable::parse(
                $nextPublication,
            );

        $seconds =
            now()->diffInSeconds(
                $publicationAt,
                false,
            );

        if ($seconds <= 0) {
            return self::MINIMUM_SECONDS;
        }

        return max(
            self::MINIMUM_SECONDS,
            min(
                SitemapCache::DEFAULT_TTL_SECONDS,
                (int) ceil(
                    $seconds,
                ),
            ),
        );
    }

    private function nextProductPublication(): ?string
    {
        $value =
            Product::query()
                ->where(
                    'status',
                    ProductStatus::Published->value,
                )
                ->whereNotNull(
                    'published_at',
                )
                ->where(
                    'published_at',
                    '>',
                    now(),
                )
                ->orderBy(
                    'published_at',
                )
                ->value(
                    'published_at',
                );

        return $value === null
            ? null
            : (string) $value;
    }

    private function nextPagePublication(): ?string
    {
        $value =
            Page::query()
                ->where(
                    'status',
                    PageStatus::Published->value,
                )
                ->whereNotNull(
                    'published_at',
                )
                ->where(
                    'published_at',
                    '>',
                    now(),
                )
                ->orderBy(
                    'published_at',
                )
                ->value(
                    'published_at',
                );

        return $value === null
            ? null
            : (string) $value;
    }
}
