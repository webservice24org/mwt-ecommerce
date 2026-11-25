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
        ?string $categorySlug,
        ?string $brandSlug,
        bool $includeCategories,
        bool $includeBrands,
    ): string {
        return implode(
            ':',
            [
                'storefront',
                'catalog',
                'filter-options',

                'category',
                $categorySlug ?? 'all',

                'brand',
                $brandSlug ?? 'all',

                'categories',
                $includeCategories
                ? '1'
                : '0',

                'brands',
                $includeBrands
                ? '1'
                : '0',
            ],
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
