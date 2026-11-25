<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Data;

final readonly class AiSeoAnalysisEnrichmentData
{
    /**
     * @param  list<AiSeoAnalysisActionEnrichmentData>  $actions
     */
    public function __construct(
        public string $summary,
        public array $actions,
    ) {}

    /**
     * @return array{
     *     summary: string,
     *     actions: list<array{
     *         finding_id: string,
     *         priority: string,
     *         explanation: string,
     *         action: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,

            'actions' => array_map(
                static fn (
                    AiSeoAnalysisActionEnrichmentData $action,
                ): array => $action->toArray(),
                $this->actions,
            ),
        ];
    }
}
