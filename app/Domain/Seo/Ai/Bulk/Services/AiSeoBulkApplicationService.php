<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkApplyBatchStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkApplyItemResult;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Domain\Seo\AiGovernance\Services\SeoAiLegacySuggestionGovernance;
use App\Models\Admin;
use App\Models\AiSeoBulkApplyBatch;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\AiSeoSuggestionApplication;
use App\Models\AiSeoSuggestionReview;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

final readonly class AiSeoBulkApplicationService
{
    private const MAX_SUGGESTIONS =
        250;

    public function __construct(
        private AiSeoTextFieldWriter $writer,
        private AiSeoBulkApplicationFreshnessService $freshness,
        private SeoAiLegacySuggestionGovernance $governance,
    ) {}

    /**
     * @param  list<int>  $suggestionIds
     *
     * @throws JsonException
     */
    public function apply(
        AiSeoBulkJob $job,
        array $suggestionIds,
        ?int $appliedByAdminId = null,
        ?string $note = null,
    ): AiSeoBulkApplyBatch {
        $suggestionIds =
            array_values(
                array_unique(
                    $suggestionIds,
                ),
            );

        if ($suggestionIds === []) {
            throw new DomainException(
                'Select at least one approved AI SEO suggestion to apply.',
            );
        }

        if (
            count(
                $suggestionIds,
            ) >
            self::MAX_SUGGESTIONS
        ) {
            throw new DomainException(
                sprintf(
                    'A bulk application may contain at most %d suggestions.',
                    self::MAX_SUGGESTIONS,
                ),
            );
        }

        $itemByRequestId =
            $this->applicationItemMap(
                $job,
            );

        if ($itemByRequestId === []) {
            throw new DomainException(
                'This bulk plan has no completed AI generation items available for application.',
            );
        }

        $suggestions =
            AiSeoSuggestion::query()
                ->whereIn(
                    'id',
                    $suggestionIds,
                )
                ->orderBy('id')
                ->get();

        if (
            $suggestions->count()
            !==
            count(
                $suggestionIds,
            )
        ) {
            throw new DomainException(
                'One or more selected AI SEO suggestions no longer exist.',
            );
        }

        foreach (
            $suggestions as $suggestion
        ) {
            if (
                ! array_key_exists(
                    $suggestion
                        ->ai_seo_request_id,
                    $itemByRequestId,
                )
            ) {
                throw new DomainException(
                    'One or more selected suggestions do not belong to this bulk AI plan.',
                );
            }
        }

        $now =
            now();

        $summary = [
            'total' => count(
                $suggestionIds,
            ),

            'applied' => 0,

            'already_applied' => 0,

            'conflict' => 0,

            'stale' => 0,

            'not_approved' => 0,

            'failed' => 0,
        ];

        $batch =
            AiSeoBulkApplyBatch::query()
                ->create([
                    'ai_seo_bulk_job_id' => $job->id,

                    'status' => AiSeoBulkApplyBatchStatus::Completed,

                    'summary' => $summary,

                    'applied_by_admin_id' => $appliedByAdminId,

                    'note' => $this->note(
                        $note,
                    ),

                    'applied_at' => $now,

                    'created_at' => $now,
                ]);

        foreach (
            $suggestions as $suggestion
        ) {
            $item =
                $itemByRequestId[
                    $suggestion
                        ->ai_seo_request_id
                ];

            try {
                $result =
                    $this->applyOne(
                        batch: $batch,

                        job: $job,

                        item: $item,

                        suggestionId: $suggestion->id,

                        appliedByAdminId: $appliedByAdminId,
                    );
            } catch (
                Throwable $exception
            ) {
                $result =
                    AiSeoBulkApplyItemResult::Failed;

                $this->recordUnexpectedFailure(
                    batch: $batch,

                    item: $item,

                    suggestion: $suggestion,

                    exception: $exception,
                );
            }

            $summary[
                $result->value
            ]++;
        }

        $hasIssues =
            $summary[
                'conflict'
            ] > 0
            || $summary[
                'stale'
            ] > 0
            || $summary[
                'not_approved'
            ] > 0
            || $summary[
                'failed'
            ] > 0;

        $batch->update([
            'status' => $hasIssues
                    ? AiSeoBulkApplyBatchStatus::CompletedWithIssues
                    : AiSeoBulkApplyBatchStatus::Completed,

            'summary' => $summary,
        ]);

        return $batch
            ->refresh()
            ->load('items');
    }

    /**
     * @throws JsonException
     */
    private function applyOne(
        AiSeoBulkApplyBatch $batch,
        AiSeoBulkJob $job,
        AiSeoBulkJobItem $item,
        int $suggestionId,
        ?int $appliedByAdminId,
    ): AiSeoBulkApplyItemResult {
        return DB::transaction(
            function () use (
                $batch,
                $job,
                $item,
                $suggestionId,
                $appliedByAdminId,
            ): AiSeoBulkApplyItemResult {
                $suggestion =
                    AiSeoSuggestion::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $suggestionId,
                        );

                $lockedItem =
                    AiSeoBulkJobItem::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $item->id,
                        );

                if (
                    $lockedItem
                        ->ai_seo_bulk_job_id
                    !==
                    $job->id
                    || $lockedItem
                        ->execution_status
                    !==
                    AiSeoBulkItemExecutionStatus::Completed
                    || $lockedItem
                        ->ai_seo_request_id
                    !==
                    $suggestion
                        ->ai_seo_request_id
                ) {
                    throw new DomainException(
                        'Bulk item is no longer eligible for application.',
                    );
                }

                $request =
                    AiSeoRequest::query()
                        ->findOrFail(
                            $suggestion
                                ->ai_seo_request_id,
                        );

                $review =
                    AiSeoSuggestionReview::query()
                        ->where(
                            'ai_seo_suggestion_id',
                            $suggestion->id,
                        )
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();

                $application =
                    AiSeoSuggestionApplication::query()
                        ->where(
                            'ai_seo_suggestion_id',
                            $suggestion->id,
                        )
                        ->lockForUpdate()
                        ->first();

                if ($application !== null) {
                    $existingAppliedByAdminId =
                        $this->positiveInt(
                            $application->getAttribute(
                                'applied_by_admin_id',
                            ),
                        )
                        ?? $appliedByAdminId;

                    $this->markGovernancePublished(
                        suggestion: $suggestion,
                        review: $review,
                        appliedByAdminId: $existingAppliedByAdminId,
                    );

                    $this->recordItem(
                        batch: $batch,

                        item: $lockedItem,

                        suggestion: $suggestion,

                        review: $review,

                        result: AiSeoBulkApplyItemResult::AlreadyApplied,

                        baselineValue: $application
                            ->baseline_value,

                        beforeValue: $application
                            ->before_value,

                        afterValue: $application
                            ->applied_value,

                        sourceSnapshotHash: $application
                            ->source_snapshot_hash,

                        currentSnapshotHash: $application
                            ->current_snapshot_hash,

                        errorCode: null,

                        errorMessage: null,
                    );

                    return AiSeoBulkApplyItemResult::AlreadyApplied;
                }

                if (
                    $review === null
                    || $review
                        ->decision
                    !==
                    AiSeoSuggestionReviewDecision::Approved
                ) {
                    $freshness =
                        $this
                            ->freshness
                            ->inspectRequest(
                                $request,
                            );

                    $this->recordItem(
                        batch: $batch,

                        item: $lockedItem,

                        suggestion: $suggestion,

                        review: $review,

                        result: AiSeoBulkApplyItemResult::NotApproved,

                        baselineValue: $this->baselineValue(
                            $request,
                            $suggestion,
                        ),

                        beforeValue: null,

                        afterValue: null,

                        sourceSnapshotHash: $freshness[
                                'source_snapshot_hash'
                            ],

                        currentSnapshotHash: $freshness[
                                'current_snapshot_hash'
                            ],

                        errorCode: 'not_approved',

                        errorMessage: 'The latest human review decision is not approved.',
                    );

                    return AiSeoBulkApplyItemResult::NotApproved;
                }

                $sourceSnapshot =
                    $this->sourceSnapshot(
                        $request,
                    );

                if ($sourceSnapshot === null) {
                    $this->recordItem(
                        batch: $batch,

                        item: $lockedItem,

                        suggestion: $suggestion,

                        review: $review,

                        result: AiSeoBulkApplyItemResult::Stale,

                        baselineValue: null,

                        beforeValue: null,

                        afterValue: null,

                        sourceSnapshotHash: null,

                        currentSnapshotHash: null,

                        errorCode: 'source_snapshot_missing',

                        errorMessage: 'The generation request has no comparable entity snapshot.',
                    );

                    return AiSeoBulkApplyItemResult::Stale;
                }

                $baselineValue =
                    $this->baselineValue(
                        $request,
                        $suggestion,
                    );

                $freshness =
                    $this
                        ->freshness
                        ->inspectRequest(
                            $request,
                        );

                $sourceSnapshotHash =
                    $freshness[
                        'source_snapshot_hash'
                    ];

                if ($sourceSnapshotHash === null) {
                    $this->recordItem(
                        batch: $batch,

                        item: $lockedItem,

                        suggestion: $suggestion,

                        review: $review,

                        result: AiSeoBulkApplyItemResult::Stale,

                        baselineValue: $baselineValue,

                        beforeValue: null,

                        afterValue: null,

                        sourceSnapshotHash: null,

                        currentSnapshotHash: $freshness[
                                'current_snapshot_hash'
                            ],

                        errorCode: 'source_snapshot_missing',

                        errorMessage: 'The generation request has no comparable entity snapshot.',
                    );

                    return AiSeoBulkApplyItemResult::Stale;
                }

                try {
                    $entity =
                        $this
                            ->writer
                            ->lockEntity(
                                $request
                                    ->entity_type,

                                $request
                                    ->entity_id,
                            );
                } catch (
                    ModelNotFoundException
                ) {
                    $this->recordItem(
                        batch: $batch,

                        item: $lockedItem,

                        suggestion: $suggestion,

                        review: $review,

                        result: AiSeoBulkApplyItemResult::Stale,

                        baselineValue: $baselineValue,

                        beforeValue: null,

                        afterValue: null,

                        sourceSnapshotHash: $sourceSnapshotHash,

                        currentSnapshotHash: null,

                        errorCode: 'entity_missing',

                        errorMessage: 'The SEO entity no longer exists.',
                    );

                    return AiSeoBulkApplyItemResult::Stale;
                }

                $beforeValue =
                    $this
                        ->writer
                        ->read(
                            $entity,
                            $suggestion
                                ->field,
                        );

                /*
                 * Field-level compare-and-set guard.
                 * A human/manual edit after AI generation wins.
                 * K.4 never force-overwrites a conflict.
                 */
                if (
                    $beforeValue !==
                    $baselineValue
                ) {
                    $currentSnapshotHash =
                        $freshness[
                            'current_snapshot_hash'
                        ];

                    $this->recordItem(
                        batch: $batch,

                        item: $lockedItem,

                        suggestion: $suggestion,

                        review: $review,

                        result: AiSeoBulkApplyItemResult::Conflict,

                        baselineValue: $baselineValue,

                        beforeValue: $beforeValue,

                        afterValue: null,

                        sourceSnapshotHash: $sourceSnapshotHash,

                        currentSnapshotHash: $currentSnapshotHash,

                        errorCode: 'field_conflict',

                        errorMessage: 'The live SEO field changed after AI generation. The current value was preserved.',
                    );

                    return AiSeoBulkApplyItemResult::Conflict;
                }

                $freshness =
                    $this
                        ->freshness
                        ->inspectRequest(
                            $request,
                        );

                $currentSnapshotHash =
                    $freshness[
                        'current_snapshot_hash'
                    ];

                if (
                    $freshness[
                        'is_stale'
                    ]
                ) {
                    $this->recordItem(
                        batch: $batch,

                        item: $lockedItem,

                        suggestion: $suggestion,

                        review: $review,

                        result: AiSeoBulkApplyItemResult::Stale,

                        baselineValue: $baselineValue,

                        beforeValue: $beforeValue,

                        afterValue: null,

                        sourceSnapshotHash: $sourceSnapshotHash,

                        currentSnapshotHash: $currentSnapshotHash,

                        errorCode: 'snapshot_stale',

                        errorMessage: 'The entity changed after AI generation. Generate and review fresh suggestions before applying.',
                    );

                    return AiSeoBulkApplyItemResult::Stale;
                }

                $this
                    ->writer
                    ->write(
                        $entity,
                        $suggestion
                            ->field,
                        $suggestion
                            ->value,
                    );

                $afterValue =
                    $this
                        ->writer
                        ->read(
                            $entity,
                            $suggestion
                                ->field,
                        );

                if (
                    $afterValue !==
                    $suggestion
                        ->value
                ) {
                    throw new DomainException(
                        'Applied SEO value could not be verified after persistence.',
                    );
                }

                $now =
                    now();

                AiSeoSuggestionApplication::query()
                    ->create([
                        'ai_seo_suggestion_id' => $suggestion->id,

                        'ai_seo_suggestion_review_id' => $review->id,

                        'ai_seo_request_id' => $request->id,

                        'ai_seo_bulk_job_id' => $job->id,

                        'ai_seo_bulk_job_item_id' => $lockedItem->id,

                        'entity_type' => $request
                            ->entity_type
                            ->value,

                        'entity_id' => $request
                            ->entity_id,

                        'field' => $suggestion
                            ->field
                            ->value,

                        'baseline_value' => $baselineValue,

                        'before_value' => $beforeValue,

                        'applied_value' => $suggestion
                            ->value,

                        'source_snapshot_hash' => $sourceSnapshotHash,

                        'current_snapshot_hash' => $currentSnapshotHash,

                        'applied_by_admin_id' => $appliedByAdminId,

                        'applied_at' => $now,

                        'created_at' => $now,
                    ]);

                $this->markGovernancePublished(
                    suggestion: $suggestion,
                    review: $review,
                    appliedByAdminId: $appliedByAdminId,
                );

                $this->recordItem(
                    batch: $batch,

                    item: $lockedItem,

                    suggestion: $suggestion,

                    review: $review,

                    result: AiSeoBulkApplyItemResult::Applied,

                    baselineValue: $baselineValue,

                    beforeValue: $beforeValue,

                    afterValue: $afterValue,

                    sourceSnapshotHash: $sourceSnapshotHash,

                    currentSnapshotHash: $currentSnapshotHash,

                    errorCode: null,

                    errorMessage: null,
                );

                return AiSeoBulkApplyItemResult::Applied;
            },
        );
    }

    private function markGovernancePublished(
        AiSeoSuggestion $suggestion,
        ?AiSeoSuggestionReview $review,
        ?int $appliedByAdminId,
    ): void {
        $actor =
            $this->governanceActor(
                appliedByAdminId: $appliedByAdminId,
                review: $review,
            );

        /*
         * Recovery-safe mirroring: the authoritative legacy review above has
         * already proven this suggestion is approved. Calling approve() here
         * is idempotent and repairs governance state for reviews completed
         * before P.3 call-site wiring was installed.
         */
        $this->governance->approve(
            $suggestion,
            $actor,
        );

        $this->governance->markPublished(
            $suggestion,
            $actor,
        );
    }

    private function governanceActor(
        ?int $appliedByAdminId,
        ?AiSeoSuggestionReview $review,
    ): Admin {
        $actorId =
            $appliedByAdminId;

        if (
            $actorId === null
            && $review !== null
        ) {
            $actorId =
                $this->positiveInt(
                    $review->getAttribute(
                        'reviewed_by_admin_id',
                    ),
                );
        }

        if ($actorId === null) {
            throw new DomainException(
                'AI SEO governance publication requires an admin actor.',
            );
        }

        return Admin::query()
            ->findOrFail(
                $actorId,
            );
    }

    private function positiveInt(
        mixed $value,
    ): ?int {
        if (
            is_int(
                $value,
            )
            && $value > 0
        ) {
            return $value;
        }

        if (
            is_string(
                $value,
            )
            && ctype_digit(
                $value,
            )
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @return array<int, AiSeoBulkJobItem>
     */
    private function applicationItemMap(
        AiSeoBulkJob $job,
    ): array {
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
                ->get();

        $map = [];

        foreach (
            $items as $item
        ) {
            if (
                $item->ai_seo_request_id ===
                null
            ) {
                continue;
            }

            $map[
                $item
                    ->ai_seo_request_id
            ] =
                $item;
        }

        return $map;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sourceSnapshot(
        AiSeoRequest $request,
    ): ?array {
        $snapshot =
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]
            ?? null;

        return is_array(
            $snapshot,
        )
            ? $snapshot
            : null;
    }

    private function baselineValue(
        AiSeoRequest $request,
        AiSeoSuggestion $suggestion,
    ): ?string {
        $snapshot =
            $this->sourceSnapshot(
                $request,
            );

        if ($snapshot === null) {
            return null;
        }

        $currentSeo =
            $snapshot[
                'current_seo'
            ]
            ?? [];

        if (! is_array($currentSeo)) {
            return null;
        }

        $value =
            $currentSeo[
                $suggestion
                    ->field
                    ->value
            ]
            ?? null;

        return is_string(
            $value,
        )
            ? $value
            : null;
    }

    private function recordUnexpectedFailure(
        AiSeoBulkApplyBatch $batch,
        AiSeoBulkJobItem $item,
        AiSeoSuggestion $suggestion,
        Throwable $exception,
    ): void {
        $message =
            trim(
                $exception
                    ->getMessage(),
            );

        $batch
            ->items()
            ->create([
                'ai_seo_bulk_job_item_id' => $item->id,

                'ai_seo_suggestion_id' => $suggestion->id,

                'ai_seo_suggestion_review_id' => null,

                'ai_seo_request_id' => $suggestion
                    ->ai_seo_request_id,

                'entity_type' => $item
                    ->entity_type
                    ->value,

                'entity_id' => $item
                    ->entity_id,

                'field' => $suggestion
                    ->field
                    ->value,

                'result' => AiSeoBulkApplyItemResult::Failed,

                'baseline_value' => null,

                'before_value' => null,

                'proposed_value' => $suggestion
                    ->value,

                'after_value' => null,

                'source_snapshot_hash' => null,

                'current_snapshot_hash' => null,

                'error_code' => 'application_failed',

                'error_message' => mb_substr(
                    $message !== ''
                        ? $message
                        : 'AI SEO suggestion application failed.',
                    0,
                    1000,
                ),

                'created_at' => now(),
            ]);
    }

    private function recordItem(
        AiSeoBulkApplyBatch $batch,
        AiSeoBulkJobItem $item,
        AiSeoSuggestion $suggestion,
        ?AiSeoSuggestionReview $review,
        AiSeoBulkApplyItemResult $result,
        ?string $baselineValue,
        ?string $beforeValue,
        ?string $afterValue,
        ?string $sourceSnapshotHash,
        ?string $currentSnapshotHash,
        ?string $errorCode,
        ?string $errorMessage,
    ): void {
        $batch
            ->items()
            ->create([
                'ai_seo_bulk_job_item_id' => $item->id,

                'ai_seo_suggestion_id' => $suggestion->id,

                'ai_seo_suggestion_review_id' => $review?->id,

                'ai_seo_request_id' => $suggestion
                    ->ai_seo_request_id,

                'entity_type' => $item
                    ->entity_type
                    ->value,

                'entity_id' => $item
                    ->entity_id,

                'field' => $suggestion
                    ->field
                    ->value,

                'result' => $result,

                'baseline_value' => $baselineValue,

                'before_value' => $beforeValue,

                'proposed_value' => $suggestion
                    ->value,

                'after_value' => $afterValue,

                'source_snapshot_hash' => $sourceSnapshotHash,

                'current_snapshot_hash' => $currentSnapshotHash,

                'error_code' => $errorCode,

                'error_message' => $errorMessage,

                'created_at' => now(),
            ]);
    }

    private function note(
        ?string $note,
    ): ?string {
        if ($note === null) {
            return null;
        }

        $note =
            trim(
                $note,
            );

        return $note === ''
            ? null
            : mb_substr(
                $note,
                0,
                1000,
            );
    }
}
