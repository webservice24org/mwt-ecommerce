<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Contracts;

use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use Generator;

interface SitemapSource
{
    public function type(): SitemapType;

    /**
     * Return only URLs that are eligible to appear
     * in the public XML sitemap.
     *
     * Implementations are responsible for:
     *
     * - entity publication/activation state
     * - SEO robots indexability
     * - final authoritative canonical URL
     * - deterministic ordering
     *
     * @return Generator<int, SitemapUrlData>
     */
    public function urls(): Generator;
}
