<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditCrawlExecutionSummaryData
{
    public function __construct(
        public int $pagesTotal,
        public int $fetchedCount,
        public int $failedCount,
        public int $discoveredCount,
        public int $linksTotal,
        public int $imagesTotal,
        public int $fetchesAttempted,
    ) {}

    /**
     * @return array{
     *     pages_total:int,
     *     fetched_count:int,
     *     failed_count:int,
     *     discovered_count:int,
     *     links_total:int,
     *     images_total:int,
     *     fetches_attempted:int
     * }
     */
    public function toArray(): array
    {
        return [
            'pages_total' => $this->pagesTotal,
            'fetched_count' => $this->fetchedCount,
            'failed_count' => $this->failedCount,
            'discovered_count' => $this->discoveredCount,
            'links_total' => $this->linksTotal,
            'images_total' => $this->imagesTotal,
            'fetches_attempted' => $this->fetchesAttempted,
        ];
    }
}
