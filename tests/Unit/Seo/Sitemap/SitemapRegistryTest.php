<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Services\SitemapRegistry;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SitemapRegistryTest extends TestCase
{
    public function test_source_can_be_registered_and_retrieved(): void
    {
        $registry =
            new SitemapRegistry;

        $source =
            $this->source(
                SitemapType::Products,
            );

        $registry->register(
            $source,
        );

        $this->assertTrue(
            $registry->has(
                SitemapType::Products,
            ),
        );

        $this->assertSame(
            $source,
            $registry->get(
                SitemapType::Products,
            ),
        );
    }

    public function test_registry_reports_missing_source(): void
    {
        $registry =
            new SitemapRegistry;

        $this->assertFalse(
            $registry->has(
                SitemapType::Products,
            ),
        );
    }

    public function test_get_throws_when_source_is_missing(): void
    {
        $registry =
            new SitemapRegistry;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $registry->get(
            SitemapType::Products,
        );
    }

    public function test_duplicate_type_cannot_be_registered(): void
    {
        $registry =
            new SitemapRegistry;

        $registry->register(
            $this->source(
                SitemapType::Products,
            ),
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        $registry->register(
            $this->source(
                SitemapType::Products,
            ),
        );
    }

    public function test_registry_returns_registered_types(): void
    {
        $registry =
            new SitemapRegistry;

        $registry->register(
            $this->source(
                SitemapType::Products,
            ),
        );

        $registry->register(
            $this->source(
                SitemapType::Categories,
            ),
        );

        $this->assertSame(
            [
                SitemapType::Products,
                SitemapType::Categories,
            ],
            $registry->types(),
        );
    }

    public function test_registry_returns_all_sources(): void
    {
        $registry =
            new SitemapRegistry;

        $productSource =
            $this->source(
                SitemapType::Products,
            );

        $categorySource =
            $this->source(
                SitemapType::Categories,
            );

        $registry->register(
            $productSource,
        );

        $registry->register(
            $categorySource,
        );

        $this->assertSame(
            [
                'products' => $productSource,

                'categories' => $categorySource,
            ],
            $registry->all(),
        );
    }

    private function source(
        SitemapType $type,
    ): SitemapSource {
        return new class($type) implements SitemapSource
        {
            public function __construct(
                private readonly SitemapType $type,
            ) {}

            public function type(): SitemapType
            {
                return $this->type;
            }

            public function urls(): Generator
            {
                yield new SitemapUrlData(
                    url: 'https://example.com/test',
                );
            }
        };
    }
}
