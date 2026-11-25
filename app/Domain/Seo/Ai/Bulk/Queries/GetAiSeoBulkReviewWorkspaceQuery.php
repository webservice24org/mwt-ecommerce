<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Queries;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkSuggestionFreshnessService;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoBulkReviewBatch;
use App\Models\AiSeoSuggestion;
use JsonException;

final readonly class GetAiSeoBulkReviewWorkspaceQuery
{
    public function __construct(
        private AiSeoBulkSuggestionFreshnessService $freshness,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function handle(int $jobId): array
    {
        $job = AiSeoBulkJob::query()->findOrFail($jobId);

        $items = AiSeoBulkJobItem::query()
            ->where('ai_seo_bulk_job_id', $job->id)
            ->where(
                'execution_status',
                AiSeoBulkItemExecutionStatus::Completed->value,
            )
            ->whereNotNull('ai_seo_request_id')
            ->orderBy('position')
            ->get();

        /** @var array<int, AiSeoBulkJobItem> $itemByRequestId */
        $itemByRequestId = [];

        /** @var list<int> $requestIds */
        $requestIds = [];

        foreach ($items as $item) {
            if ($item->ai_seo_request_id === null) {
                continue;
            }

            $requestIds[] = $item->ai_seo_request_id;
            $itemByRequestId[$item->ai_seo_request_id] = $item;
        }

        $suggestions = AiSeoSuggestion::query()
            ->with([
                'request',
                'latestReview',
            ])
            ->whereIn('ai_seo_request_id', $requestIds)
            ->orderBy('ai_seo_request_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        /** @var array<int, array{
         *     state: string,
         *     is_stale: bool,
         *     source_snapshot_hash: string|null,
         *     current_snapshot_hash: string|null,
         *     current_seo: array<string, string|null>
         * }> $freshnessByRequest
         */
        $freshnessByRequest = [];

        /** @var list<array<string, mixed>> $rows */
        $rows = [];

        $summary = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'fresh' => 0,
            'stale' => 0,
            'fresh_pending' => 0,
        ];

        foreach ($suggestions as $suggestion) {
            $requestId = (int) $suggestion->ai_seo_request_id;
            $item = $itemByRequestId[$requestId] ?? null;

            if ($item === null) {
                continue;
            }

            if (! array_key_exists($requestId, $freshnessByRequest)) {
                $freshnessByRequest[$requestId] = $this
                    ->freshness
                    ->inspectRequest($suggestion->request);
            }

            /** @var array{
             *     state: string,
             *     is_stale: bool,
             *     source_snapshot_hash: string|null,
             *     current_snapshot_hash: string|null,
             *     current_seo: array<string, string|null>
             * } $freshness
             */
            $freshness = $freshnessByRequest[$requestId];

            $sourceSnapshot = $suggestion
                ->request
                ->input_snapshot['entity_snapshot']
                ?? [];

            if (! is_array($sourceSnapshot)) {
                $sourceSnapshot = [];
            }

            $sourceSeo = $sourceSnapshot['current_seo'] ?? [];

            if (! is_array($sourceSeo)) {
                $sourceSeo = [];
            }

            /** @var array<string, mixed> $sourceSeo */
            $field = $suggestion->field->value;
            $existingValue = $sourceSeo[$field] ?? null;
            $currentValue = $freshness['current_seo'][$field] ?? null;
            $reviewStatus = $suggestion->latestReview?->decision->value ?? 'pending';

            $summary['total']++;

            if ($reviewStatus === 'approved') {
                $summary['approved']++;
            } elseif ($reviewStatus === 'rejected') {
                $summary['rejected']++;
            } else {
                $summary['pending']++;
            }

            if ($freshness['is_stale']) {
                $summary['stale']++;
            } else {
                $summary['fresh']++;

                if ($reviewStatus === 'pending') {
                    $summary['fresh_pending']++;
                }
            }

            $rows[] = [
                'id' => $suggestion->id,
                'bulk_item_id' => $item->id,
                'request_id' => $requestId,
                'entity_type' => $item->entity_type->value,
                'entity_id' => $item->entity_id,
                'entity_name' => $item->entity_name,
                'entity_slug' => $item->entity_slug,
                'field' => $field,
                'field_label' => $this->fieldLabel($field),
                'existing_value' => is_string($existingValue)
                    ? $existingValue
                    : null,
                'current_value' => is_string($currentValue)
                    ? $currentValue
                    : null,
                'suggested_value' => $suggestion->value,
                'rationale' => $suggestion->rationale,
                'review_status' => $reviewStatus,
                'reviewed_at' => $suggestion
                    ->latestReview
                    ?->reviewed_at
                    ->toIso8601String(),
                'freshness' => [
                    'state' => $freshness['state'],
                    'is_stale' => $freshness['is_stale'],
                ],
                'can_approve' => ! $freshness['is_stale'],
                'can_reject' => true,
            ];
        }

        $recentBatches = AiSeoBulkReviewBatch::query()
            ->where('ai_seo_bulk_job_id', $job->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(
                static fn (
                    AiSeoBulkReviewBatch $batch,
                ): array => [
                    'id' => $batch->id,
                    'decision' => $batch->decision->value,
                    'status' => $batch->status->value,
                    'summary' => $batch->summary,
                    'reviewed_by_admin_id' => $batch->reviewed_by_admin_id,
                    'note' => $batch->note,
                    'reviewed_at' => $batch->reviewed_at->toIso8601String(),
                ],
            )
            ->values()
            ->all();

        return [
            'plan' => [
                'id' => $job->id,
                'reference' => $job->reference,
                'status' => $job->status->value,
                'plan_hash' => $job->plan_hash,
            ],
            'summary' => $summary,
            'suggestions' => $rows,
            'recent_batches' => $recentBatches,
        ];
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'meta_title' => 'Meta title',
            'meta_description' => 'Meta description',
            'og_title' => 'Open Graph title',
            'og_description' => 'Open Graph description',
            'twitter_title' => 'X/Twitter title',
            'twitter_description' => 'X/Twitter description',
            default => str($field)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }
}
