<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Data\SitemapPartData;
use App\Domain\Seo\Sitemap\Data\SitemapSplitPolicy;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use Generator;

final readonly class SitemapSplitter
{
    public function __construct(
        private SitemapSplitPolicy $policy,
    ) {}

    /**
     * @return Generator<int, SitemapPartData>
     */
    public function split(
        SitemapSource $source,
    ): Generator {
        /** @var list<SitemapUrlData> $buffer */
        $buffer = [];

        $partNumber = 1;

        foreach (
            $source->urls() as $url
        ) {
            $buffer[] =
                $url;

            if (
                count($buffer)
                < $this
                    ->policy
                    ->maxUrlsPerFile
            ) {
                continue;
            }

            yield new SitemapPartData(
                type: $source->type(),

                partNumber: $partNumber,

                urls: $buffer,
            );

            $buffer = [];

            $partNumber++;
        }

        if ($buffer === []) {
            return;
        }

        yield new SitemapPartData(
            type: $source->type(),

            partNumber: $partNumber,

            urls: $buffer,
        );
    }
}
