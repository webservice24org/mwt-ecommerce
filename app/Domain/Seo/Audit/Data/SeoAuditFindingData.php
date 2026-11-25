<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;

final readonly class SeoAuditFindingData
{
    /**
     * @param  array<string, mixed>  $evidence
     */
    public function __construct(
        public SeoAuditRule $rule,
        public SeoAuditSeverity $severity,
        public SeoAuditSubjectType $subjectType,
        public string $message,
        public ?string $subjectKey = null,
        public ?string $url = null,
        public ?string $sourceUrl = null,
        public array $evidence = [],
        public ?string $remediation = null,
    ) {}
}
