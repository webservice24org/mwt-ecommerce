<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Data;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;

final readonly class AiSeoAnalysisResultData
{
    /**
     * @param  array<string, array{
     *     points: int,
     *     max_points: int
     * }>  $breakdown
     * @param  list<AiSeoAnalysisRuleResultData>  $rules
     * @param  list<AiSeoAnalysisFindingData>  $findings
     */
    public function __construct(
        public string $version,
        public int $score,
        public AiSeoAnalysisGrade $grade,
        public array $breakdown,
        public array $rules,
        public array $findings,
    ) {}

    /**
     * @return array{
     *     version: string,
     *     score: int,
     *     grade: string,
     *     breakdown: array<string, array{
     *         points: int,
     *         max_points: int
     *     }>,
     *     rules: list<array{
     *         rule_id: string,
     *         category: string,
     *         points: int,
     *         max_points: int,
     *         passed: bool
     *     }>,
     *     findings: list<array{
     *         id: string,
     *         category: string,
     *         severity: string,
     *         title: string,
     *         message: string,
     *         recommendation: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,

            'score' => $this->score,

            'grade' => $this->grade->value,

            'breakdown' => $this->breakdown,

            'rules' => array_map(
                static fn (
                    AiSeoAnalysisRuleResultData $rule,
                ): array => $rule->toArray(),
                $this->rules,
            ),

            'findings' => array_map(
                static fn (
                    AiSeoAnalysisFindingData $finding,
                ): array => $finding->toArray(),
                $this->findings,
            ),
        ];
    }
}
