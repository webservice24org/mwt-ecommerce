<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Queries;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkApplicationPreviewService;
use App\Models\AiSeoBulkApplyBatch;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoSuggestion;
use App\Models\AiSeoSuggestionApplication;
use JsonException;

final readonly class GetAiSeoBulkApplicationWorkspaceQuery
{
    public function __construct(
        private AiSeoBulkApplicationPreviewService $preview,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function handle(
        int $jobId,
    ): array {
        $job =
            AiSeoBulkJob::query()
                ->findOrFail(
                    $jobId,
                );

        $items =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $job->id,
                )
                ->where(
                    'execution_status',
                    AiSeoBulkItemExecutionStatus::Completed
                        ->value,
                )
                ->whereNotNull(
                    'ai_seo_request_id',
                )
                ->orderBy(
                    'position',
                )
                ->get();

        /** @var array<int, AiSeoBulkJobItem> $itemByRequestId */
        $itemByRequestId = [];

        /** @var list<int> $requestIds */
        $requestIds = [];

        foreach (
            $items as $item
        ) {
            if (
                $item->ai_seo_request_id ===
                null
            ) {
                continue;
            }

            $requestIds[] =
                $item
                    ->ai_seo_request_id;

            $itemByRequestId[
                $item
                    ->ai_seo_request_id
            ] =
                $item;
        }

        $suggestions =
            AiSeoSuggestion::query()
                ->with([
                    'request',
                    'latestReview',
                ])
                ->whereIn(
                    'ai_seo_request_id',
                    $requestIds,
                )
                ->orderBy(
                    'ai_seo_request_id',
                )
                ->orderBy(
                    'position',
                )
                ->orderBy(
                    'id',
                )
                ->get();

        $suggestionIds =
            $suggestions
                ->pluck('id')
                ->map(
                    static fn (
                        mixed $id,
                    ): int => (int) $id,
                )
                ->all();

        $applications =
            AiSeoSuggestionApplication::query()
                ->whereIn(
                    'ai_seo_suggestion_id',
                    $suggestionIds,
                )
                ->get()
                ->keyBy(
                    'ai_seo_suggestion_id',
                );

        $summary = [
            'total' => 0,

            'ready' => 0,

            'already_applied' => 0,

            'conflict' => 0,

            'stale' => 0,

            'not_approved' => 0,
        ];

        $rows = [];

        foreach (
            $suggestions as $suggestion
        ) {
            $item =
                $itemByRequestId[
                    $suggestion
                        ->ai_seo_request_id
                ]
                ?? null;

            if ($item === null) {
                continue;
            }

            $application =
                $applications
                    ->get(
                        $suggestion->id,
                    );

            $inspection =
                $this
                    ->preview
                    ->inspect(
                        $suggestion,
                        $application,
                    );

            $status =
                $inspection[
                    'status'
                ];

            $summary[
                'total'
            ]++;

            if (
                array_key_exists(
                    $status,
                    $summary,
                )
            ) {
                $summary[
                    $status
                ]++;
            }

            $rows[] = [
                'id' => $suggestion->id,

                'bulk_item_id' => $item->id,

                'request_id' => $suggestion
                    ->ai_seo_request_id,

                'entity_type' => $item
                    ->entity_type
                    ->value,

                'entity_id' => $item
                    ->entity_id,

                'entity_name' => $item
                    ->entity_name,

                'entity_slug' => $item
                    ->entity_slug,

                'field' => $suggestion
                    ->field
                    ->value,

                'field_label' => $this->fieldLabel(
                    $suggestion
                        ->field
                        ->value,
                ),

                'suggested_value' => $suggestion
                    ->value,

                'rationale' => $suggestion
                    ->rationale,

                'review_status' => $inspection[
                        'review_status'
                    ],

                'application_status' => $status,

                'can_apply' => $inspection[
                        'can_apply'
                    ],

                'baseline_value' => $inspection[
                        'baseline_value'
                    ],

                'current_value' => $inspection[
                        'current_value'
                    ],

                'source_snapshot_hash' => $inspection[
                        'source_snapshot_hash'
                    ],

                'current_snapshot_hash' => $inspection[
                        'current_snapshot_hash'
                    ],

                'application_id' => $inspection[
                        'application_id'
                    ],

                'applied_at' => $inspection[
                        'applied_at'
                    ],

                'reason' => $inspection[
                        'reason'
                    ],
            ];
        }

        $recentBatches =
            AiSeoBulkApplyBatch::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $job->id,
                )
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(
                    static fn (
                        AiSeoBulkApplyBatch $batch,
                    ): array => [
                        'id' => $batch->id,

                        'status' => $batch
                            ->status
                            ->value,

                        'summary' => $batch
                            ->summary,

                        'applied_by_admin_id' => $batch
                            ->applied_by_admin_id,

                        'note' => $batch
                            ->note,

                        'applied_at' => $batch
                            ->applied_at
                            ->toIso8601String(),
                    ],
                )
                ->values()
                ->all();

        return [
            'plan' => [
                'id' => $job->id,

                'reference' => $job
                    ->reference,

                'status' => $job
                    ->status
                    ->value,

                'plan_hash' => $job
                    ->plan_hash,
            ],

            'summary' => $summary,

            'suggestions' => $rows,

            'recent_batches' => $recentBatches,
        ];
    }

    private function fieldLabel(
        string $field,
    ): string {
        return match ($field) {
            'meta_title' => 'Meta title',

            'meta_description' => 'Meta description',

            'og_title' => 'Open Graph title',

            'og_description' => 'Open Graph description',

            'twitter_title' => 'X/Twitter title',

            'twitter_description' => 'X/Twitter description',

            default => str(
                $field,
            )
                ->replace(
                    '_',
                    ' ',
                )
                ->title()
                ->toString(),
        };
    }
}
