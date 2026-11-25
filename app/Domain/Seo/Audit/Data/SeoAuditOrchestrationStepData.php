<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditOrchestrationStepData
{
    public function __construct(
        public bool $completed,
        public int $queuePass,
        public int $pagesTotal,
        public int $fetchedCount,
        public int $failedCount,
        public int $discoveredCount,
        public int $urlsScanned,
    ) {}
}
