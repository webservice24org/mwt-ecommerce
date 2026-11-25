<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditCrawlInventorySummaryData
{
    public function __construct(
        public int $pagesTotal,
        public int $discoveredCount,
        public int $fetchedCount,
        public int $failedCount,
        public int $scannedCount,
        public int $linksTotal,
    ) {}

    /**
     * @return array{
     *     pages_total:int,
     *     discovered_count:int,
     *     fetched_count:int,
     *     failed_count:int,
     *     scanned_count:int,
     *     links_total:int
     * }
     */
    public function toArray(): array
    {
        return [
            'pages_total' => $this->pagesTotal,
            'discovered_count' => $this->discoveredCount,
            'fetched_count' => $this->fetchedCount,
            'failed_count' => $this->failedCount,
            'scanned_count' => $this->scannedCount,
            'links_total' => $this->linksTotal,
        ];
    }
}
