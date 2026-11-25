<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Services\SitemapRegistry;
use App\Domain\Seo\Sitemap\Sources\ProductSitemapSource;
use Tests\TestCase;

final class SitemapRegistryIntegrationTest extends TestCase
{
    public function test_product_source_is_registered(): void
    {
        $registry =
            app(
                SitemapRegistry::class,
            );

        $this->assertTrue(
            $registry->has(
                SitemapType::Products,
            ),
        );

        $this->assertInstanceOf(
            ProductSitemapSource::class,
            $registry->get(
                SitemapType::Products,
            ),
        );
    }
}
