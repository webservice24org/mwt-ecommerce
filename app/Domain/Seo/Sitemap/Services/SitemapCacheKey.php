<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use App\Domain\Seo\Sitemap\Enums\SitemapType;

final class SitemapCacheKey
{
    public static function index(): string
    {
        return 'index';
    }

    public static function part(
        SitemapType $type,
        int $partNumber,
    ): string {
        return sprintf(
            'part:%s:%d',
            $type->value,
            $partNumber,
        );
    }
}
