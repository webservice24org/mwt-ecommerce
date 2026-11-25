<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantFeedExportFileData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportEventType;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportFormat;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Queries\GetMerchantReadinessWorkspaceQuery;
use App\Models\Admin;
use App\Models\MerchantFeedExport;
use App\Models\MerchantFeedExportEvent;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class MerchantFeedExportLifecycle
{
    public function __construct(
        private GetMerchantReadinessWorkspaceQuery $workspace,
        private MerchantFeedExportSnapshotFactory $snapshots,
        private MerchantProductFeedCatalogBuilder $catalog,
    ) {}

    public function request(
        Admin $admin,
        MerchantFeedExportFormat $format = MerchantFeedExportFormat::Tsv,
    ): MerchantFeedExport {
        $catalog =
            $this->catalog
                ->build();

        $workspace =
            $this->workspace
                ->fromCatalog(
                    $catalog,
                );

        $snapshot =
            $this->snapshots
                ->make(
                    workspace: $workspace,

                    format: $format,

                    catalog: $catalog,
                );

        return DB::transaction(
            function () use (
                $admin,
                $format,
                $snapshot,
            ): MerchantFeedExport {
                $now = now();

                $export =
                    MerchantFeedExport::query()
                        ->create([
                            'public_id' => Str::uuid()
                                ->toString(),

                            'requested_by_admin_id' => $admin->getKey(),

                            'requested_by_name' => $admin->name,

                            'requested_by_email' => $admin->email,

                            'status' => MerchantFeedExportStatus::Queued,

                            'format' => $format,

                            'schema_version' => $snapshot->schemaVersion,

                            'readiness_policy_version' => $snapshot->readinessPolicyVersion,

                            'merchant_policy_version' => $snapshot->merchantPolicyVersion,

                            'snapshot_hash' => $snapshot->hash,

                            'snapshot' => $snapshot->snapshot,

                            'products_considered' => $snapshot->productsConsidered,

                            'item_candidates' => $snapshot->itemCandidates,

                            'exportable_count' => $snapshot->exportableCount,

                            'excluded_count' => $snapshot->excludedCount,

                            'out_of_stock_count' => $snapshot->outOfStockCount,

                            'blocker_count' => $snapshot->blockerCount,

                            'warning_count' => $snapshot->warningCount,

                            'info_count' => $snapshot->infoCount,

                            'truncated' => $snapshot->truncated,

                            'max_items' => $snapshot->maxItems,

                            'attempt_count' => 0,

                            'queued_at' => $now,
                        ]);

                $this->event(
                    export: $export,

                    type: MerchantFeedExportEventType::Requested,

                    from: null,

                    to: MerchantFeedExportStatus::Queued,

                    actor: $admin,

                    metadata: [
                        'snapshot_hash' => $snapshot->hash,

                        'schema_version' => $snapshot->schemaVersion,

                        'exportable_count' => $snapshot->exportableCount,

                        'excluded_count' => $snapshot->excludedCount,
                    ],
                );

                return $export;
            },
        );
    }

    public function start(
        MerchantFeedExport $export,
    ): MerchantFeedExport {
        return DB::transaction(
            function () use ($export): MerchantFeedExport {
                $current =
                    $this->locked(
                        $export,
                    );

                if (
                    $current->status ===
                    MerchantFeedExportStatus::Processing
                    || $current->status ===
                    MerchantFeedExportStatus::Completed
                ) {
                    return $current;
                }

                if (
                    $current->status !==
                    MerchantFeedExportStatus::Queued
                ) {
                    throw new DomainException(
                        'Only queued merchant feed exports may start processing.',
                    );
                }

                $from =
                    $current->status;

                $current->forceFill([
                    'status' => MerchantFeedExportStatus::Processing,

                    'attempt_count' => $current->attempt_count + 1,

                    'started_at' => now(),

                    'completed_at' => null,

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,
                ])->save();

                $this->event(
                    export: $current,

                    type: MerchantFeedExportEventType::Started,

                    from: $from,

                    to: MerchantFeedExportStatus::Processing,

                    actor: null,

                    metadata: [
                        'attempt' => $current->attempt_count,
                    ],
                );

                return $current;
            },
        );
    }

    public function complete(
        MerchantFeedExport $export,
        MerchantFeedExportFileData $file,
    ): MerchantFeedExport {
        $this->validateFile(
            $file,
        );

        return DB::transaction(
            function () use (
                $export,
                $file,
            ): MerchantFeedExport {
                $current =
                    $this->locked(
                        $export,
                    );

                if (
                    $current->status ===
                    MerchantFeedExportStatus::Completed
                ) {
                    if (
                        $current->file_sha256 ===
                        strtolower(
                            $file->sha256,
                        )
                        && $current->file_size ===
                            $file->size
                    ) {
                        return $current;
                    }

                    throw new DomainException(
                        'Completed merchant feed export cannot be replaced by different file metadata.',
                    );
                }

                if (
                    $current->status !==
                    MerchantFeedExportStatus::Processing
                ) {
                    throw new DomainException(
                        'Only processing merchant feed exports may complete.',
                    );
                }

                $from =
                    $current->status;

                $current->forceFill([
                    'status' => MerchantFeedExportStatus::Completed,

                    'file_disk' => $file->disk,

                    'file_path' => $file->path,

                    'file_name' => $file->name,

                    'file_mime_type' => $file->mimeType,

                    'file_size' => $file->size,

                    'file_sha256' => strtolower(
                        $file->sha256,
                    ),

                    'completed_at' => now(),

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,
                ])->save();

                $this->event(
                    export: $current,

                    type: MerchantFeedExportEventType::Completed,

                    from: $from,

                    to: MerchantFeedExportStatus::Completed,

                    actor: null,

                    metadata: [
                        'file_name' => $file->name,

                        'file_size' => $file->size,

                        'file_sha256' => strtolower(
                            $file->sha256,
                        ),
                    ],
                );

                return $current;
            },
        );
    }

    public function fail(
        MerchantFeedExport $export,
        string $failureCode,
        string $failureMessage,
    ): MerchantFeedExport {
        $failureCode =
            mb_substr(
                trim(
                    $failureCode,
                ),
                0,
                100,
            );

        $failureMessage =
            mb_substr(
                trim(
                    $failureMessage,
                ),
                0,
                4000,
            );

        if (
            $failureCode === ''
            || $failureMessage === ''
        ) {
            throw new InvalidArgumentException(
                'Merchant export failure code and message are required.',
            );
        }

        return DB::transaction(
            function () use (
                $export,
                $failureCode,
                $failureMessage,
            ): MerchantFeedExport {
                $current =
                    $this->locked(
                        $export,
                    );

                if (
                    $current->status ===
                    MerchantFeedExportStatus::Failed
                ) {
                    if (
                        $current->failure_code ===
                            $failureCode
                        && $current->failure_message ===
                            $failureMessage
                    ) {
                        return $current;
                    }

                    throw new DomainException(
                        'Failed merchant feed export already records a different failure.',
                    );
                }

                if (
                    $current->status ===
                    MerchantFeedExportStatus::Completed
                ) {
                    throw new DomainException(
                        'Completed merchant feed export cannot fail.',
                    );
                }

                $from =
                    $current->status;

                $current->forceFill([
                    'status' => MerchantFeedExportStatus::Failed,

                    'failure_code' => $failureCode,

                    'failure_message' => $failureMessage,

                    'failed_at' => now(),
                ])->save();

                $this->event(
                    export: $current,

                    type: MerchantFeedExportEventType::Failed,

                    from: $from,

                    to: MerchantFeedExportStatus::Failed,

                    actor: null,

                    metadata: [
                        'failure_code' => $current->failure_code,
                    ],
                );

                return $current;
            },
        );
    }

    public function retry(
        MerchantFeedExport $export,
        Admin $admin,
    ): MerchantFeedExport {
        return DB::transaction(
            function () use (
                $export,
                $admin,
            ): MerchantFeedExport {
                $current =
                    $this->locked(
                        $export,
                    );

                if (
                    $current->status ===
                    MerchantFeedExportStatus::Queued
                ) {
                    return $current;
                }

                if (
                    $current->status !==
                    MerchantFeedExportStatus::Failed
                ) {
                    throw new DomainException(
                        'Only failed merchant feed exports may be retried.',
                    );
                }

                if (
                    $current->failure_code ===
                    'snapshot_changed'
                ) {
                    throw new DomainException(
                        'Snapshot-changed merchant feed exports must be replaced by a new export.',
                    );
                }

                $from =
                    $current->status;

                $current->forceFill([
                    'status' => MerchantFeedExportStatus::Queued,

                    'queued_at' => now(),

                    'started_at' => null,

                    'completed_at' => null,

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,

                    'file_disk' => null,

                    'file_path' => null,

                    'file_name' => null,

                    'file_mime_type' => null,

                    'file_size' => null,

                    'file_sha256' => null,
                ])->save();

                $this->event(
                    export: $current,

                    type: MerchantFeedExportEventType::Retried,

                    from: $from,

                    to: MerchantFeedExportStatus::Queued,

                    actor: $admin,

                    metadata: [
                        'previous_attempts' => $current->attempt_count,
                    ],
                );

                return $current;
            },
        );
    }

    public function recordDownloadRequested(
        MerchantFeedExport $export,
        Admin $admin,
    ): MerchantFeedExport {
        return DB::transaction(
            function () use (
                $export,
                $admin,
            ): MerchantFeedExport {
                $current =
                    $this->locked(
                        $export,
                    );

                if (
                    $current->status !==
                    MerchantFeedExportStatus::Completed
                ) {
                    throw new DomainException(
                        'Only completed merchant feed exports may be downloaded.',
                    );
                }

                $this->event(
                    export: $current,

                    type: MerchantFeedExportEventType::DownloadRequested,

                    from: MerchantFeedExportStatus::Completed,

                    to: MerchantFeedExportStatus::Completed,

                    actor: $admin,

                    metadata: [
                        'file_name' => $current->file_name,

                        'file_sha256' => $current->file_sha256,
                    ],
                );

                return $current;
            },
        );
    }

    private function locked(
        MerchantFeedExport $export,
    ): MerchantFeedExport {
        return MerchantFeedExport::query()
            ->whereKey(
                $export->getKey(),
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function event(
        MerchantFeedExport $export,
        MerchantFeedExportEventType $type,
        ?MerchantFeedExportStatus $from,
        MerchantFeedExportStatus $to,
        ?Admin $actor,
        ?array $metadata,
    ): void {
        MerchantFeedExportEvent::query()
            ->create([
                'merchant_feed_export_id' => $export->id,

                'event_type' => $type,

                'from_status' => $from,

                'to_status' => $to,

                'actor_admin_id' => $actor?->getKey(),

                'actor_name' => $actor?->name,

                'actor_email' => $actor?->email,

                'metadata' => $metadata,

                'occurred_at' => now(),
            ]);
    }

    private function validateFile(
        MerchantFeedExportFileData $file,
    ): void {
        if (
            trim($file->disk) === ''
            || trim($file->path) === ''
            || trim($file->name) === ''
            || trim($file->mimeType) === ''
            || $file->size < 0
            || preg_match(
                '/^[a-f0-9]{64}$/i',
                $file->sha256,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Merchant feed export file metadata is invalid.',
            );
        }
    }
}
