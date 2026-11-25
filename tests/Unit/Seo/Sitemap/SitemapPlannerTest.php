<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Data\SitemapSplitPolicy;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Services\SitemapPlanner;
use App\Domain\Seo\Sitemap\Services\SitemapRegistry;
use App\Domain\Seo\Sitemap\Services\SitemapSplitter;
use Generator;
use PHPUnit\Framework\TestCase;

final class SitemapPlannerTest extends TestCase
{
    public function test_index_contains_every_generated_part(): void
    {
        $registry =
            new SitemapRegistry;

        $registry->register(
            $this->source(
                SitemapType::Products,
                5,
            ),
        );

        $registry->register(
            $this->source(
                SitemapType::Categories,
                1,
            ),
        );

        $planner =
            new SitemapPlanner(
                registry: $registry,

                splitter: new SitemapSplitter(
                    new SitemapSplitPolicy(
                        2,
                    ),
                ),
            );

        $entries =
            iterator_to_array(
                $planner
                    ->indexEntries(),
                false,
            );

        self::assertCount(
            4,
            $entries,
        );

        self::assertSame(
            [
                'products-sitemap-1.xml',
                'products-sitemap-2.xml',
                'products-sitemap-3.xml',
                'categories-sitemap-1.xml',
            ],
            array_map(
                static fn ($entry): string => $entry->filename,

                $entries,
            ),
        );
    }

    public function test_specific_part_can_be_resolved(): void
    {
        $registry =
            new SitemapRegistry;

        $registry->register(
            $this->source(
                SitemapType::Products,
                5,
            ),
        );

        $planner =
            new SitemapPlanner(
                $registry,

                new SitemapSplitter(
                    new SitemapSplitPolicy(
                        2,
                    ),
                ),
            );

        $part =
            $planner->part(
                SitemapType::Products,
                2,
            );

        self::assertNotNull(
            $part,
        );

        self::assertSame(
            2,
            $part->partNumber,
        );

        self::assertSame(
            2,
            $part->count(),
        );

        self::assertSame(
            'products-sitemap-2.xml',
            $part->filename(),
        );
    }

    public function test_non_existing_part_returns_null(): void
    {
        $registry =
            new SitemapRegistry;

        $registry->register(
            $this->source(
                SitemapType::Products,
                1,
            ),
        );

        $planner =
            new SitemapPlanner(
                $registry,

                new SitemapSplitter(
                    new SitemapSplitPolicy(
                        2,
                    ),
                ),
            );

        self::assertNull(
            $planner->part(
                SitemapType::Products,
                2,
            ),
        );
    }

    private function source(
        SitemapType $type,
        int $count,
    ): SitemapSource {
        return new class($type, $count) implements SitemapSource
        {
            public function __construct(
                private readonly SitemapType $type,
                private readonly int $count,
            ) {}

            public function type(): SitemapType
            {
                return $this->type;
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
                        sprintf(
                            'https://example.com/%s/item-%d',
                            $this->type->value,
                            $index,
                        ),
                    );
                }
            }
        };
    }
}
