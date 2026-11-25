<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Data;

use InvalidArgumentException;

final readonly class SitemapSplitPolicy
{
    public function __construct(
        public int $maxUrlsPerFile = 45_000,
    ) {
        if ($this->maxUrlsPerFile < 1) {
            throw new InvalidArgumentException(
                'Sitemap max URLs per file must be greater than zero.',
            );
        }

        if ($this->maxUrlsPerFile > 50_000) {
            throw new InvalidArgumentException(
                'Sitemap max URLs per file may not exceed 50,000.',
            );
        }
    }
}
