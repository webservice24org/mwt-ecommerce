<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Data\SitemapSplitPolicy;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Services\SitemapSplitter;
use Generator;
use PHPUnit\Framework\TestCase;

final class SitemapSplitterTest extends TestCase
{
    public function test_urls_are_split_into_deterministic_parts(): void
    {
        $source =
            $this->source(
                5,
            );

        $splitter =
            new SitemapSplitter(
                new SitemapSplitPolicy(
                    maxUrlsPerFile: 2,
                ),
            );

        $parts =
            iterator_to_array(
                $splitter->split(
                    $source,
                ),
                false,
            );

        self::assertCount(
            3,
            $parts,
        );

        self::assertSame(
            [
                2,
                2,
                1,
            ],
            array_map(
                static fn ($part): int => $part->count(),

                $parts,
            ),
        );

        self::assertSame(
            [
                'products-sitemap-1.xml',
                'products-sitemap-2.xml',
                'products-sitemap-3.xml',
            ],
            array_map(
                static fn ($part): string => $part->filename(),

                $parts,
            ),
        );
    }

    public function test_exact_limit_does_not_create_empty_extra_part(): void
    {
        $splitter =
            new SitemapSplitter(
                new SitemapSplitPolicy(
                    2,
                ),
            );

        $parts =
            iterator_to_array(
                $splitter->split(
                    $this->source(
                        4,
                    ),
                ),
                false,
            );

        self::assertCount(
            2,
            $parts,
        );

        self::assertSame(
            2,
            $parts[0]->count(),
        );

        self::assertSame(
            2,
            $parts[1]->count(),
        );
    }

    public function test_empty_source_creates_no_parts(): void
    {
        $splitter =
            new SitemapSplitter(
                new SitemapSplitPolicy(
                    2,
                ),
            );

        self::assertSame(
            [],
            iterator_to_array(
                $splitter->split(
                    $this->source(
                        0,
                    ),
                ),
                false,
            ),
        );
    }

    public function test_split_policy_rejects_more_than_protocol_limit(): void
    {
        $this->expectException(
            \InvalidArgumentException::class,
        );

        new SitemapSplitPolicy(
            50_001,
        );
    }

    private function source(
        int $count,
    ): SitemapSource {
        return new class($count) implements SitemapSource
        {
            public function __construct(
                private readonly int $count,
            ) {}

            public function type(): SitemapType
            {
                return SitemapType::Products;
            }

            /**
             * @return Generator<int, SitemapUrlData>
             */
            public function urls(): Generator
            {
                for (
                    $index = 1;
                    $index <= $this->count;
                    $index++
                ) {
                    yield new SitemapUrlData(
                        url: "https://example.com/products/product-{$index}",
                    );
                }
            }
        };
    }
}
