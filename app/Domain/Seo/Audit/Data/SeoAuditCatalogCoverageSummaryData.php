<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditCatalogCoverageSummaryData
{
    public function __construct(
        public int $publishedProducts,
        public int $activeCategories,
        public int $catalogUrls,
        public int $newPages,
        public int $taggedExistingPages,
    ) {}
}
