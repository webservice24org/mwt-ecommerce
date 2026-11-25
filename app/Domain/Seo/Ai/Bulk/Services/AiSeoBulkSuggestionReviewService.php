<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkReviewBatchStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkReviewItemResult;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Domain\Seo\Ai\Workspace\Services\AiSeoSuggestionReviewService;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoBulkReviewBatch;
use App\Models\AiSeoSuggestion;
use DomainException;
use JsonException;
use Throwable;

final readonly class AiSeoBulkSuggestionReviewService
{
    private const MAX_SUGGESTIONS = 250;

    public function __construct(
        private AiSeoSuggestionReviewService $reviews,
        private AiSeoBulkSuggestionFreshnessService $freshness,
    ) {}

    /**
     * @param  list<int>  $suggestionIds
     *
     * @throws DomainException
     * @throws JsonException
     */
    public function review(
        AiSeoBulkJob $job,
        array $suggestionIds,
        AiSeoSuggestionReviewDecision $decision,
        ?int $reviewedByAdminId = null,
        ?string $note = null,
    ): AiSeoBulkReviewBatch {
        $suggestionIds = array_values(
            array_unique(
                array_map(
                    static fn (int $id): int => $id,
                    $suggestionIds,
                ),
            ),
        );

        if ($suggestionIds === []) {
            throw new DomainException(
                'Select at least one AI SEO suggestion to review.',
            );
        }

        if (count($suggestionIds) > self::MAX_SUGGESTIONS) {
            throw new DomainException(
                sprintf(
                    'A bulk review may contain at most %d suggestions.',
                    self::MAX_SUGGESTIONS,
                ),
            );
        }

        $itemByRequestId = $this->reviewableItemMap($job);

        if ($itemByRequestId === []) {
            throw new DomainException(
                'This bulk plan has no completed AI generation items available for review.',
            );
        }

        $suggestions = AiSeoSuggestion::query()
            ->with([
                'request',
                'latestReview',
            ])
            ->whereIn('id', $suggestionIds)
            ->orderBy('id')
            ->get();

        if ($suggestions->count() !== count($suggestionIds)) {
            throw new DomainException(
                'One or more selected AI SEO suggestions no longer exist.',
            );
        }

        foreach ($suggestions as $suggestion) {
            $requestId = (int) $suggestion->ai_seo_request_id;

            if (! array_key_exists($requestId, $itemByRequestId)) {
                throw new DomainException(
                    'One or more selected suggestions do not belong to this bulk AI plan.',
                );
            }
        }

        $now = now();
        $batch = AiSeoBulkReviewBatch::query()->create([
            'ai_seo_bulk_job_id' => $job->id,
            'decision' => $decision,
            'status' => AiSeoBulkReviewBatchStatus::Completed,
            'summary' => [
                'total' => count($suggestionIds),
                'approved' => 0,
                'rejected' => 0,
                'already_decided' => 0,
                'blocked_stale' => 0,
                'failed' => 0,
            ],
            'reviewed_by_admin_id' => $reviewedByAdminId,
            'note' => $this->note($note),
            'reviewed_at' => $now,
            'created_at' => $now,
        ]);

        $summary = $batch->summary;

        foreach ($suggestions as $suggestion) {
            $requestId = (int) $suggestion->ai_seo_request_id;
            $item = $itemByRequestId[$requestId];
            $previousDecision = $suggestion->latestReview?->decision->value;
            $freshness = $this->freshness->inspect($suggestion);

            if (
                $decision === AiSeoSuggestionReviewDecision::Approved
                && $freshness['is_stale']
            ) {
                $this->recordItem(
                    batch: $batch,
                    item: $item,
                    suggestion: $suggestion,
                    result: AiSeoBulkReviewItemResult::BlockedStale,
                    freshnessState: $freshness['state'],
                    previousDecision: $previousDecision,
                    resultingDecision: $previousDecision,
                    errorCode: 'stale_suggestion',
                    errorMessage: 'Stale AI SEO suggestions cannot be approved. Generate fresh suggestions first.',
                );

                $summary['blocked_stale']++;

                continue;
            }

            if ($previousDecision === $decision->value) {
                $this->recordItem(
                    batch: $batch,
                    item: $item,
                    suggestion: $suggestion,
                    result: AiSeoBulkReviewItemResult::AlreadyDecided,
                    freshnessState: $freshness['state'],
                    previousDecision: $previousDecision,
                    resultingDecision: $previousDecision,
                );

                $summary['already_decided']++;

                continue;
            }

            try {
                $review = $decision === AiSeoSuggestionReviewDecision::Approved
                    ? $this->reviews->approve(
                        suggestion: $suggestion,
                        reviewedByAdminId: $reviewedByAdminId,
                        note: $this->note($note),
                    )
                    : $this->reviews->reject(
                        suggestion: $suggestion,
                        reviewedByAdminId: $reviewedByAdminId,
                        note: $this->note($note),
                    );

                $result = $decision === AiSeoSuggestionReviewDecision::Approved
                    ? AiSeoBulkReviewItemResult::Approved
                    : AiSeoBulkReviewItemResult::Rejected;

                $this->recordItem(
                    batch: $batch,
                    item: $item,
                    suggestion: $suggestion,
                    result: $result,
                    freshnessState: $freshness['state'],
                    previousDecision: $previousDecision,
                    resultingDecision: $review->decision->value,
                );

                if ($result === AiSeoBulkReviewItemResult::Approved) {
                    $summary['approved']++;
                } else {
                    $summary['rejected']++;
                }
            } catch (Throwable $exception) {
                $this->recordFailure(
                    batch: $batch,
                    item: $item,
                    suggestion: $suggestion,
                    freshnessState: $freshness['state'],
                    previousDecision: $previousDecision,
                    code: $exception instanceof DomainException
                        ? 'review_rejected'
                        : 'review_failed',
                    exception: $exception,
                );

                $summary['failed']++;
            }
        }

        $hasIssues = $summary['blocked_stale'] > 0 || $summary['failed'] > 0;

        $batch->update([
            'status' => $hasIssues
                ? AiSeoBulkReviewBatchStatus::CompletedWithIssues
                : AiSeoBulkReviewBatchStatus::Completed,
            'summary' => $summary,
        ]);

        return $batch
            ->refresh()
            ->load('items');
    }

    /** @return array<int, AiSeoBulkJobItem> */
    private function reviewableItemMap(AiSeoBulkJob $job): array
    {
        $items = AiSeoBulkJobItem::query()
            ->where('ai_seo_bulk_job_id', $job->id)
            ->where(
                'execution_status',
                AiSeoBulkItemExecutionStatus::Completed->value,
            )
            ->whereNotNull('ai_seo_request_id')
            ->get();

        $map = [];

        foreach ($items as $item) {
            if ($item->ai_seo_request_id === null) {
                continue;
            }

            $map[$item->ai_seo_request_id] = $item;
        }

        return $map;
    }

    private function recordFailure(
        AiSeoBulkReviewBatch $batch,
        AiSeoBulkJobItem $item,
        AiSeoSuggestion $suggestion,
        string $freshnessState,
        ?string $previousDecision,
        string $code,
        Throwable $exception,
    ): void {
        $message = trim($exception->getMessage());

        $this->recordItem(
            batch: $batch,
            item: $item,
            suggestion: $suggestion,
            result: AiSeoBulkReviewItemResult::Failed,
            freshnessState: $freshnessState,
            previousDecision: $previousDecision,
            resultingDecision: $previousDecision,
            errorCode: $code,
            errorMessage: mb_substr(
                $message !== ''
                    ? $message
                    : 'AI SEO suggestion review failed.',
                0,
                1000,
            ),
        );
    }

    private function recordItem(
        AiSeoBulkReviewBatch $batch,
        AiSeoBulkJobItem $item,
        AiSeoSuggestion $suggestion,
        AiSeoBulkReviewItemResult $result,
        string $freshnessState,
        ?string $previousDecision,
        ?string $resultingDecision,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        $batch->items()->create([
            'ai_seo_bulk_job_item_id' => $item->id,
            'ai_seo_suggestion_id' => $suggestion->id,
            'ai_seo_request_id' => $suggestion->ai_seo_request_id,
            'field' => $suggestion->field->value,
            'result' => $result,
            'previous_decision' => $previousDecision,
            'resulting_decision' => $resultingDecision,
            'freshness_state' => $freshnessState,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }

    private function note(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $note = trim($note);

        return $note === ''
            ? null
            : mb_substr($note, 0, 1000);
    }
}
