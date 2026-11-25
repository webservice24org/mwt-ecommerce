<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Queries;

use App\Models\AiSeoAnalysisEnrichment;
use Illuminate\Database\Eloquent\Collection;

final class AiSeoAnalysisEnrichmentHistoryQuery
{
    public function latest(
        int $analysisId,
    ): ?AiSeoAnalysisEnrichment {
        return AiSeoAnalysisEnrichment::query()
            ->where(
                'ai_seo_analysis_id',
                $analysisId,
            )
            ->latest(
                'created_at',
            )
            ->latest(
                'id',
            )
            ->first();
    }

    /**
     * @return Collection<int, AiSeoAnalysisEnrichment>
     */
    public function history(
        int $analysisId,
        int $limit = 20,
    ): Collection {
        $limit =
            max(
                1,
                min(
                    $limit,
                    100,
                ),
            );

        return AiSeoAnalysisEnrichment::query()
            ->where(
                'ai_seo_analysis_id',
                $analysisId,
            )
            ->latest(
                'created_at',
            )
            ->latest(
                'id',
            )
            ->limit(
                $limit,
            )
            ->get();
    }
}
