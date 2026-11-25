<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Data;

use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisActionPriority;

final readonly class AiSeoAnalysisActionEnrichmentData
{
    public function __construct(
        public string $findingId,
        public AiSeoAnalysisActionPriority $priority,
        public string $explanation,
        public string $action,
    ) {}

    /**
     * @return array{
     *     finding_id: string,
     *     priority: string,
     *     explanation: string,
     *     action: string
     * }
     */
    public function toArray(): array
    {
        return [
            'finding_id' => $this->findingId,

            'priority' => $this->priority->value,

            'explanation' => $this->explanation,

            'action' => $this->action,
        ];
    }
}
