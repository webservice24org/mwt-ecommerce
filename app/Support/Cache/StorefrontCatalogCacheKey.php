<?php

declare(strict_types=1);

namespace App\Support\Cache;

final class StorefrontCatalogCacheKey
{
    public static function product(string $slug): string
    {
        return sprintf(
            'product:%s',
            self::segment($slug),
        );
    }

    public static function category(string $slug): string
    {
        return sprintf(
            'category:%s',
            self::segment($slug),
        );
    }

    public static function home(): string
    {
        return 'home';
    }

    public static function filterOptions(
        ?string $categorySlug = null,
        bool $includeCategories = true,
    ): string {
        $scope = $categorySlug === null
            ? 'all'
            : sprintf(
                'category:%s',
                self::segment($categorySlug),
            );

        return sprintf(
            'filter-options:%s:categories:%s',
            $scope,
            $includeCategories ? 'yes' : 'no',
        );
    }

    private static function segment(
        string $value,
    ): string {
        return hash(
            'sha256',
            $value,
        );
    }
}
