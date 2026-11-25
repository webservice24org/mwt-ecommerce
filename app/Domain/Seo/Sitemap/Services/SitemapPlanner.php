<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use App\Domain\Seo\Sitemap\Data\SitemapIndexEntryData;
use App\Domain\Seo\Sitemap\Data\SitemapPartData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use Generator;

final readonly class SitemapPlanner
{
    public function __construct(
        private SitemapRegistry $registry,
        private SitemapSplitter $splitter,
    ) {}

    /**
     * @return Generator<int, SitemapPartData>
     */
    public function parts(): Generator
    {
        foreach (
            $this->registry->all() as $source
        ) {
            yield from $this
                ->splitter
                ->split(
                    $source,
                );
        }
    }

    /**
     * @return Generator<int, SitemapPartData>
     */
    public function partsFor(
        SitemapType $type,
    ): Generator {
        $source =
            $this
                ->registry
                ->get(
                    $type,
                );

        yield from $this
            ->splitter
            ->split(
                $source,
            );
    }

    public function part(
        SitemapType $type,
        int $partNumber,
    ): ?SitemapPartData {
        if ($partNumber < 1) {
            return null;
        }

        foreach (
            $this->partsFor(
                $type,
            ) as $part
        ) {
            if (
                $part->partNumber
                === $partNumber
            ) {
                return $part;
            }

            if (
                $part->partNumber
                > $partNumber
            ) {
                return null;
            }
        }

        return null;
    }

    /**
     * @return Generator<int, SitemapIndexEntryData>
     */
    public function indexEntries(): Generator
    {
        foreach (
            $this->parts() as $part
        ) {
            yield SitemapIndexEntryData::fromPart(
                $part,
            );
        }
    }
}
