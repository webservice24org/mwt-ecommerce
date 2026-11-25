<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemState;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkPlannedAction;
use App\Domain\Seo\Ai\Bulk\Exceptions\AiSeoBulkItemBusyException;
use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkCandidatesQuery;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Domain\Seo\Ai\Exceptions\AiSeoSuggestionValidationException;
use App\Domain\Seo\Ai\Services\AiSeoDraftRequestService;
use App\Domain\Seo\Ai\Services\AiSeoRequestLifecycleManager;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Services\AiSeoSuggestionGenerationService;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

final readonly class AiSeoBulkItemExecutionService
{
    public function __construct(
        private GetAiSeoBulkCandidatesQuery $candidates,
        private AiSeoDraftRequestService $drafts,
        private AiSeoSuggestionGenerationService $generation,
        private AiSeoRequestLifecycleManager $lifecycle,
        private AiSeoSnapshotHasher $hasher,
        private AiSeoBulkJobProgressService $progress,
    ) {}

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function execute(
        int $itemId,
    ): void {
        $item =
            $this->prepare(
                $itemId,
            );

        if ($item === null) {
            $this
                ->progress
                ->refreshForItem(
                    $itemId,
                );

            return;
        }

        try {
            try {
                $candidate =
                    $this
                        ->candidates
                        ->inspect(
                            $item
                                ->entity_type,

                            $item
                                ->entity_id,
                        );
            } catch (
                ModelNotFoundException
            ) {
                $this->markDrifted(
                    $item->id,
                    'entity_missing',
                    'The planned SEO entity no longer exists.',
                );

                $this
                    ->progress
                    ->refreshForItem(
                        $item->id,
                    );

                return;
            }

            if (
                ! hash_equals(
                    $item
                        ->snapshot_hash,
                    $candidate
                        ->currentSnapshotHash,
                )
            ) {
                $this->markDrifted(
                    $item->id,
                    'snapshot_drift',
                    'The entity changed after this bulk plan was created.',
                );

                $this
                    ->progress
                    ->refreshForItem(
                        $item->id,
                    );

                return;
            }

            $request =
                $this->linkedRequest(
                    $item,
                );

            if ($request !== null) {
                $requestHash =
                    $this->requestEntitySnapshotHash(
                        $request,
                    );

                if (
                    $requestHash === null
                    || ! hash_equals(
                        $item
                            ->snapshot_hash,
                        $requestHash,
                    )
                ) {
                    if (
                        in_array(
                            $request->status,
                            [
                                AiSeoRequestStatus::Pending,
                                AiSeoRequestStatus::Processing,
                            ],
                            true,
                        )
                    ) {
                        $this
                            ->lifecycle
                            ->cancel(
                                $request,
                            );
                    }

                    $this->markDrifted(
                        $item->id,
                        'request_snapshot_mismatch',
                        'The linked AI SEO request does not match the immutable bulk-plan snapshot.',
                    );

                    $this
                        ->progress
                        ->refreshForItem(
                            $item->id,
                        );

                    return;
                }

                if (
                    $request->status ===
                    AiSeoRequestStatus::Completed
                ) {
                    $this->markCompleted(
                        $item->id,
                        $request->id,
                    );

                    $this
                        ->progress
                        ->refreshForItem(
                            $item->id,
                        );

                    return;
                }

                if (
                    $request->status ===
                    AiSeoRequestStatus::Processing
                ) {
                    if (
                        ! $this->processingRequestIsStale(
                            $request,
                        )
                    ) {
                        throw new AiSeoBulkItemBusyException(
                            sprintf(
                                'AI SEO request %d is already processing for bulk item %d.',
                                $request->id,
                                $item->id,
                            ),
                        );
                    }

                    $this
                        ->lifecycle
                        ->fail(
                            request: $request,

                            errorCode: 'bulk_processing_timeout',

                            errorMessage: 'Bulk execution recovered an AI SEO request that remained processing beyond the configured timeout.',
                        );

                    $request =
                        null;
                } elseif (
                    in_array(
                        $request->status,
                        [
                            AiSeoRequestStatus::Failed,
                            AiSeoRequestStatus::Cancelled,
                        ],
                        true,
                    )
                ) {
                    $request =
                        null;
                }
            }

            /*
             * Another workflow may have generated a fresh
             * completed request after K.1 planning but before
             * this worker started. Reuse that result and avoid
             * duplicate provider cost.
             */
            if (
                $request === null
                && $candidate->state ===
                    AiSeoBulkItemState::Fresh
            ) {
                $this->markSkipped(
                    $item->id,
                    'fresh_request_exists',
                    'A current completed AI metadata request already exists; duplicate generation was skipped.',
                );

                $this
                    ->progress
                    ->refreshForItem(
                        $item->id,
                    );

                return;
            }

            if ($request === null) {
                $request =
                    $this
                        ->drafts
                        ->create(
                            entityType: $item
                                ->entity_type,

                            entityId: $item
                                ->entity_id,

                            requestedByAdminId: $item
                                ->job
                                ->planned_by_admin_id,
                        );

                $requestHash =
                    $this->requestEntitySnapshotHash(
                        $request,
                    );

                if (
                    $requestHash === null
                    || ! hash_equals(
                        $item
                            ->snapshot_hash,
                        $requestHash,
                    )
                ) {
                    $this
                        ->lifecycle
                        ->cancel(
                            $request,
                        );

                    $this->linkRequest(
                        $item->id,
                        $request->id,
                    );

                    $this->markDrifted(
                        $item->id,
                        'snapshot_drift_before_provider',
                        'The entity changed while the bulk item was preparing its provider request.',
                    );

                    $this
                        ->progress
                        ->refreshForItem(
                            $item->id,
                        );

                    return;
                }

                $this->linkRequest(
                    $item->id,
                    $request->id,
                );
            }

            $completed =
                $this
                    ->generation
                    ->execute(
                        $request,
                    );

            $this->markCompleted(
                $item->id,
                $completed->id,
            );

            $this
                ->progress
                ->refreshForItem(
                    $item->id,
                );
        } catch (
            AiSeoBulkItemBusyException $exception
        ) {
            throw $exception;
        } catch (
            Throwable $exception
        ) {
            $this->markFailed(
                $item->id,
                $exception,
            );

            throw $exception;
        }
    }

    public function markRetryQueued(
        int $itemId,
        Throwable $exception,
    ): void {
        DB::transaction(
            function () use (
                $itemId,
                $exception,
            ): void {
                $item =
                    AiSeoBulkJobItem::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $itemId,
                        );

                if (
                    in_array(
                        $item->execution_status,
                        [
                            AiSeoBulkItemExecutionStatus::Completed,
                            AiSeoBulkItemExecutionStatus::Skipped,
                            AiSeoBulkItemExecutionStatus::Drifted,
                        ],
                        true,
                    )
                ) {
                    return;
                }

                $item->update([
                    'execution_status' => AiSeoBulkItemExecutionStatus::Queued,

                    'queued_at' => now(),

                    'completed_at' => null,

                    'execution_error_code' => $this->errorCode(
                        $exception,
                    ),

                    'execution_error_message' => $this->errorMessage(
                        $exception,
                    ),
                ]);
            },
        );

        $this
            ->progress
            ->refreshForItem(
                $itemId,
            );
    }

    public function markTerminalFailure(
        int $itemId,
        Throwable $exception,
    ): void {
        $this->markFailed(
            $itemId,
            $exception,
        );

        $this
            ->progress
            ->refreshForItem(
                $itemId,
            );
    }

    /**
     * @throws AiSeoBulkItemBusyException
     */
    private function prepare(
        int $itemId,
    ): ?AiSeoBulkJobItem {
        return DB::transaction(
            function () use (
                $itemId,
            ): ?AiSeoBulkJobItem {
                $item =
                    AiSeoBulkJobItem::query()
                        ->with(
                            'job',
                        )
                        ->lockForUpdate()
                        ->findOrFail(
                            $itemId,
                        );

                if (
                    $item
                        ->execution_status
                        ->terminal()
                ) {
                    return null;
                }

                if (
                    $item->planned_action ===
                    AiSeoBulkPlannedAction::Skip
                ) {
                    $item->update([
                        'execution_status' => AiSeoBulkItemExecutionStatus::Skipped,

                        'completed_at' => now(),
                    ]);

                    return null;
                }

                if (
                    $item->execution_status ===
                    AiSeoBulkItemExecutionStatus::Processing
                ) {
                    throw new AiSeoBulkItemBusyException(
                        sprintf(
                            'Bulk item %d is already processing.',
                            $item->id,
                        ),
                    );
                }

                $now =
                    now();

                $item->update([
                    'execution_status' => AiSeoBulkItemExecutionStatus::Processing,

                    'attempt_count' => $item
                        ->attempt_count
                        + 1,

                    'started_at' => $item->started_at
                        ?? $now,

                    'last_attempt_at' => $now,

                    'completed_at' => null,

                    'execution_error_code' => null,

                    'execution_error_message' => null,
                ]);

                return $item->refresh();
            },
        );
    }

    private function linkedRequest(
        AiSeoBulkJobItem $item,
    ): ?AiSeoRequest {
        if (
            $item->ai_seo_request_id ===
            null
        ) {
            return null;
        }

        return AiSeoRequest::query()
            ->find(
                $item
                    ->ai_seo_request_id,
            );
    }

    /**
     * @throws JsonException
     */
    private function requestEntitySnapshotHash(
        AiSeoRequest $request,
    ): ?string {
        $snapshot =
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]
            ?? null;

        if (! is_array($snapshot)) {
            return null;
        }

        return $this
            ->hasher
            ->hash(
                $snapshot,
            );
    }

    private function processingRequestIsStale(
        AiSeoRequest $request,
    ): bool {
        if (
            $request->started_at ===
            null
        ) {
            return true;
        }

        $seconds =
            max(
                60,
                (int) config(
                    'ai-seo-bulk.processing_stale_seconds',
                    900,
                ),
            );

        return $request
            ->started_at
            ->lessThanOrEqualTo(
                now()
                    ->subSeconds(
                        $seconds,
                    ),
            );
    }

    private function linkRequest(
        int $itemId,
        int $requestId,
    ): void {
        AiSeoBulkJobItem::query()
            ->whereKey(
                $itemId,
            )
            ->update([
                'ai_seo_request_id' => $requestId,
            ]);
    }

    private function markCompleted(
        int $itemId,
        int $requestId,
    ): void {
        AiSeoBulkJobItem::query()
            ->whereKey(
                $itemId,
            )
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Completed,

                'ai_seo_request_id' => $requestId,

                'execution_error_code' => null,

                'execution_error_message' => null,

                'completed_at' => now(),
            ]);
    }

    private function markSkipped(
        int $itemId,
        string $code,
        string $message,
    ): void {
        AiSeoBulkJobItem::query()
            ->whereKey(
                $itemId,
            )
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Skipped,

                'execution_error_code' => $code,

                'execution_error_message' => $message,

                'completed_at' => now(),
            ]);
    }

    private function markDrifted(
        int $itemId,
        string $code,
        string $message,
    ): void {
        AiSeoBulkJobItem::query()
            ->whereKey(
                $itemId,
            )
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Drifted,

                'execution_error_code' => $code,

                'execution_error_message' => $message,

                'completed_at' => now(),
            ]);
    }

    private function markFailed(
        int $itemId,
        Throwable $exception,
    ): void {
        AiSeoBulkJobItem::query()
            ->whereKey(
                $itemId,
            )
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Failed,

                'execution_error_code' => $this->errorCode(
                    $exception,
                ),

                'execution_error_message' => $this->errorMessage(
                    $exception,
                ),

                'completed_at' => now(),
            ]);
    }

    private function errorCode(
        Throwable $exception,
    ): string {
        return match (true) {
            $exception instanceof AiSeoProviderException => $exception
                ->errorCode,

            $exception instanceof AiSeoSuggestionValidationException => 'invalid_provider_output',

            $exception instanceof AiSeoBulkItemBusyException => 'bulk_item_busy',

            default => 'bulk_item_execution_failed',
        };
    }

    private function errorMessage(
        Throwable $exception,
    ): string {
        $message =
            trim(
                $exception
                    ->getMessage(),
            );

        if ($message === '') {
            $message =
                'Bulk AI SEO item execution failed.';
        }

        return mb_substr(
            $message,
            0,
            1000,
        );
    }
}
