<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Queries;

use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\AiSeoAnalysis;
use Illuminate\Database\Eloquent\Collection;

final class AiSeoAnalysisHistoryQuery
{
    public function latest(
        AiSeoEntityType $entityType,
        int $entityId,
    ): ?AiSeoAnalysis {
        return AiSeoAnalysis::query()
            ->where(
                'entity_type',
                $entityType->value,
            )
            ->where(
                'entity_id',
                $entityId,
            )
            ->latest(
                'analyzed_at',
            )
            ->latest(
                'id',
            )
            ->first();
    }

    /**
     * @return Collection<int, AiSeoAnalysis>
     */
    public function history(
        AiSeoEntityType $entityType,
        int $entityId,
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

        return AiSeoAnalysis::query()
            ->where(
                'entity_type',
                $entityType->value,
            )
            ->where(
                'entity_id',
                $entityId,
            )
            ->latest(
                'analyzed_at',
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
