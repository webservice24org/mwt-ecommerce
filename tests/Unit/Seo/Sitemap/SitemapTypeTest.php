<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Enums\SitemapType;
use PHPUnit\Framework\TestCase;

final class SitemapTypeTest extends TestCase
{
    public function test_sitemap_types_have_stable_values(): void
    {
        $this->assertSame(
            'products',
            SitemapType::Products->value,
        );

        $this->assertSame(
            'categories',
            SitemapType::Categories->value,
        );

        $this->assertSame(
            'brands',
            SitemapType::Brands->value,
        );

        $this->assertSame(
            'pages',
            SitemapType::Pages->value,
        );
    }

    public function test_sitemap_types_have_deterministic_filenames(): void
    {
        $this->assertSame(
            'products-sitemap.xml',
            SitemapType::Products
                ->filename(),
        );

        $this->assertSame(
            'categories-sitemap.xml',
            SitemapType::Categories
                ->filename(),
        );

        $this->assertSame(
            'brands-sitemap.xml',
            SitemapType::Brands
                ->filename(),
        );

        $this->assertSame(
            'pages-sitemap.xml',
            SitemapType::Pages
                ->filename(),
        );
    }
}
