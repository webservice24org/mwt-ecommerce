<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Queries;

use App\Models\AiSeoBulkJob;

final readonly class GetAiSeoBulkWorkspaceQuery
{
    public function __construct(
        private GetAiSeoBulkCandidatesQuery $candidates,
    ) {}

    /**
     * @param  list<string>  $entityTypes
     * @return array<string, mixed>
     */
    public function handle(
        array $entityTypes = [],
        ?string $search = null,
        ?string $state = null,
    ): array {
        $candidateData = $this
            ->candidates
            ->handle(
                entityTypes: $entityTypes,
                search: $search,
                state: $state,
            );

        $plans = AiSeoBulkJob::query()
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(
                static fn (
                    AiSeoBulkJob $job,
                ): array => [
                    'id' => $job->id,
                    'reference' => $job->reference,
                    'status' => $job->status->value,
                    'plan_hash' => $job->plan_hash,
                    'entity_types' => $job->entity_types,
                    'summary' => $job->summary,
                    'planned_by_admin_id' => $job->planned_by_admin_id,
                    'planned_at' => $job->planned_at->toIso8601String(),
                ],
            )
            ->values()
            ->all();

        return $candidateData
            + [
                'recent_plans' => $plans,
            ];
    }
}
