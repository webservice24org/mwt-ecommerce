<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;

final readonly class SeoAuditTechnicalRuleEvaluationSummaryData
{
    /**
     * @param  list<SeoAuditRule>  $deferredRules
     */
    public function __construct(
        public int $rulesEvaluated,
        public int $findingsRecorded,
        public int $errorCount,
        public int $warningCount,
        public int $pagesEvaluated,
        public int $linksEvaluated,
        public int $imagesEvaluated,
        public array $deferredRules,
    ) {}
}
