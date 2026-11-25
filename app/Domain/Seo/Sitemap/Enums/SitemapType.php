<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Enums;

enum SitemapType: string
{
    case Products = 'products';
    case Categories = 'categories';
    case Brands = 'brands';
    case Pages = 'pages';

    public function filename(): string
    {
        return "{$this->value}-sitemap.xml";
    }

    public function label(): string
    {
        return match ($this) {
            self::Products => 'Products',

            self::Categories => 'Categories',

            self::Brands => 'Brands',

            self::Pages => 'Pages',
        };
    }
}
