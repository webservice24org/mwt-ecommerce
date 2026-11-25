<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditImageHealthChunkSummaryData
{
    public function __construct(
        public int $observations,
        public int $resourcesTotal,
        public int $observedResources,
        public int $processedThisPass,
        public int $remainingResources,
    ) {}
}
