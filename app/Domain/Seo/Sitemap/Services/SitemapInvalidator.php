<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

final readonly class SitemapInvalidator
{
    public function __construct(
        private SitemapCache $cache,
    ) {}

    public function invalidate(): void
    {
        $this
            ->cache
            ->invalidate();
    }
}
