<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkPlannedAction;
use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkCandidatesQuery;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\AiSeoBulkJob;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

final readonly class AiSeoBulkPlanService
{
    private const MAX_TARGETS = 250;

    public function __construct(
        private GetAiSeoBulkCandidatesQuery $candidates,
    ) {}

    /**
     * @param  list<array{
     *     entity_type: string,
     *     entity_id: int
     * }>  $targets
     * @param  array<string, mixed>  $filters
     *
     * @throws JsonException
     */
    public function create(
        array $targets,
        array $filters = [],
        ?int $plannedByAdminId = null,
    ): AiSeoBulkJob {
        if ($targets === []) {
            throw new DomainException(
                'Select at least one SEO entity before creating a bulk plan.',
            );
        }

        if (count($targets) > self::MAX_TARGETS) {
            throw new DomainException(
                sprintf(
                    'A bulk plan may contain at most %d entities.',
                    self::MAX_TARGETS,
                ),
            );
        }

        $unique = [];
        $inspected = [];

        foreach ($targets as $target) {
            $type = AiSeoEntityType::tryFrom(
                $target['entity_type'],
            );

            $entityId =
                (int) $target[
                    'entity_id'
                ];

            if (
                $type === null
                || $entityId < 1
            ) {
                throw new DomainException(
                    'Bulk plan contains an invalid SEO entity target.',
                );
            }

            $key = $type->value
                .':'
                .$entityId;

            if (isset($unique[$key])) {
                throw new DomainException(
                    'Bulk plan contains a duplicate SEO entity target.',
                );
            }

            $unique[$key] = true;

            $inspected[] = $this
                ->candidates
                ->inspect(
                    $type,
                    $entityId,
                );
        }

        $planItems = [];
        $entityTypes = [];
        $summary = [
            'total' => count($inspected),
            'generate' => 0,
            'skip' => 0,
            'ready' => 0,
            'stale' => 0,
            'fresh' => 0,
        ];

        foreach (
            $inspected as $position => $candidate
        ) {
            $eligible = $candidate
                ->state
                ->isEligibleForGeneration();

            $action = $eligible
                ? AiSeoBulkPlannedAction::Generate
                : AiSeoBulkPlannedAction::Skip;

            $summary[$action->value]++;
            $summary[
                $candidate->state->value
            ]++;

            $entityTypes[
                $candidate->entityType->value
            ] = true;

            $planItems[] = [
                'position' => $position,
                'entity_type' => $candidate->entityType->value,
                'entity_id' => $candidate->entityId,
                'entity_name' => $candidate->name,
                'entity_slug' => $candidate->slug,
                'snapshot_hash' => $candidate->currentSnapshotHash,
                'latest_request_id' => $candidate->latestRequestId,
                'latest_request_snapshot_hash' => $candidate->latestRequestSnapshotHash,
                'state' => $candidate->state->value,
                'planned_action' => $action->value,
                'reason' => $candidate->reason,
            ];
        }

        $entityTypeValues = array_keys($entityTypes);
        sort($entityTypeValues);

        $normalizedFilters = $this
            ->normalizedFilters($filters);

        $planHash = hash(
            'sha256',
            json_encode(
                [
                    'entity_types' => $entityTypeValues,
                    'filters' => $normalizedFilters,
                    'items' => $planItems,
                ],
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES,
            ),
        );

        return DB::transaction(
            function () use (
                $planHash,
                $entityTypeValues,
                $normalizedFilters,
                $summary,
                $plannedByAdminId,
                $planItems,
            ): AiSeoBulkJob {
                $now = now();

                $job = AiSeoBulkJob::query()
                    ->create([
                        'reference' => 'BULK-'
                            .strtoupper(
                                (string) Str::ulid(),
                            ),
                        'status' => AiSeoBulkJobStatus::Planned,
                        'plan_hash' => $planHash,
                        'entity_types' => $entityTypeValues,
                        'filters' => $normalizedFilters,
                        'summary' => $summary,
                        'planned_by_admin_id' => $plannedByAdminId,
                        'planned_at' => $now,
                        'created_at' => $now,
                    ]);

                foreach ($planItems as $item) {
                    $job
                        ->items()
                        ->create(
                            $item
                            + [
                                'created_at' => $now,
                            ],
                        );
                }

                return $job
                    ->refresh()
                    ->load('items');
            },
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizedFilters(
        array $filters,
    ): array {
        ksort($filters);

        return $filters;
    }
}
