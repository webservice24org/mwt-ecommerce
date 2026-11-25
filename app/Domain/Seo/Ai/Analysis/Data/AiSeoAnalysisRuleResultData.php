<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Data;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisCategory;

final readonly class AiSeoAnalysisRuleResultData
{
    public function __construct(
        public string $ruleId,
        public AiSeoAnalysisCategory $category,
        public int $points,
        public int $maxPoints,
        public ?AiSeoAnalysisFindingData $finding = null,
    ) {}

    /**
     * @return array{
     *     rule_id: string,
     *     category: string,
     *     points: int,
     *     max_points: int,
     *     passed: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'rule_id' => $this->ruleId,

            'category' => $this->category->value,

            'points' => $this->points,

            'max_points' => $this->maxPoints,

            'passed' => $this->points
                === $this->maxPoints,
        ];
    }
}
