<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditInternalLinkOpportunitySummaryData
{
    public function __construct(
        public int $orphanTargets,
        public int $evaluatedTargets,
        public int $opportunities,
        public int $targetsWithoutCandidates,
        public bool $truncated,
    ) {}

    /** @return array{orphan_targets:int,evaluated_targets:int,opportunities:int,targets_without_candidates:int,truncated:bool} */
    public function toArray(): array
    {
        return [
            'orphan_targets' => $this->orphanTargets,
            'evaluated_targets' => $this->evaluatedTargets,
            'opportunities' => $this->opportunities,
            'targets_without_candidates' => $this->targetsWithoutCandidates,
            'truncated' => $this->truncated,
        ];
    }
}
