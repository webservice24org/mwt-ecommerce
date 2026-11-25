<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditSummaryData
{
    public function __construct(
        public int $findingsTotal,
        public int $criticalCount,
        public int $errorCount,
        public int $warningCount,
        public int $infoCount,
    ) {}
}
