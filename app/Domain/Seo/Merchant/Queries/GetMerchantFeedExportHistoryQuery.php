<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Queries;

use App\Domain\Seo\Merchant\Data\MerchantFeedExportHistoryData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Models\MerchantFeedExport;

final readonly class GetMerchantFeedExportHistoryQuery
{
    private const PER_PAGE = 10;

    public function get(
        int $page = 1,
    ): MerchantFeedExportHistoryData {
        $total =
            MerchantFeedExport::query()
                ->count();

        $lastPage =
            max(
                1,
                (int) ceil(
                    $total /
                    self::PER_PAGE,
                ),
            );

        $currentPage =
            min(
                max(
                    1,
                    $page,
                ),
                $lastPage,
            );

        $offset =
            ($currentPage - 1)
            * self::PER_PAGE;

        $exports =
            MerchantFeedExport::query()
                ->select([
                    'id',
                    'public_id',
                    'requested_by_name',
                    'requested_by_email',
                    'status',
                    'format',
                    'schema_version',
                    'readiness_policy_version',
                    'merchant_policy_version',
                    'products_considered',
                    'item_candidates',
                    'exportable_count',
                    'excluded_count',
                    'out_of_stock_count',
                    'blocker_count',
                    'warning_count',
                    'info_count',
                    'truncated',
                    'max_items',
                    'attempt_count',
                    'file_name',
                    'file_mime_type',
                    'file_size',
                    'file_sha256',
                    'failure_code',
                    'failure_message',
                    'queued_at',
                    'started_at',
                    'completed_at',
                    'failed_at',
                    'created_at',
                ])
                ->orderByDesc(
                    'id',
                )
                ->offset(
                    $offset,
                )
                ->limit(
                    self::PER_PAGE,
                )
                ->get();

        /** @var list<array<string, mixed>> $items */
        $items =
            $exports
                ->map(
                    static fn (
                        MerchantFeedExport $export,
                    ): array => [
                        'public_id' => $export->public_id,

                        'requested_by' => [
                            'name' => $export->requested_by_name,

                            'email' => $export->requested_by_email,
                        ],

                        'status' => $export->status->value,

                        'format' => $export->format->value,

                        'schema_version' => $export->schema_version,

                        'readiness_policy_version' => $export->readiness_policy_version,

                        'merchant_policy_version' => $export->merchant_policy_version,

                        'products_considered' => $export->products_considered,

                        'item_candidates' => $export->item_candidates,

                        'exportable_count' => $export->exportable_count,

                        'excluded_count' => $export->excluded_count,

                        'out_of_stock_count' => $export->out_of_stock_count,

                        'blocker_count' => $export->blocker_count,

                        'warning_count' => $export->warning_count,

                        'info_count' => $export->info_count,

                        'truncated' => $export->truncated,

                        'max_items' => $export->max_items,

                        'attempt_count' => $export->attempt_count,

                        'file' => [
                            'name' => $export->file_name,

                            'mime_type' => $export->file_mime_type,

                            'size' => $export->file_size,

                            'sha256' => $export->file_sha256,
                        ],

                        'failure' => [
                            'code' => $export->failure_code,

                            'message' => $export->failure_message,
                        ],

                        'can_download' => self::canDownload(
                            $export,
                        ),

                        'can_retry' => self::canRetry(
                            $export,
                        ),

                        'queued_at' => $export->queued_at
                            ->toIso8601String(),

                        'started_at' => $export->started_at
                            ?->toIso8601String(),

                        'completed_at' => $export->completed_at
                            ?->toIso8601String(),

                        'failed_at' => $export->failed_at
                            ?->toIso8601String(),

                        'created_at' => $export->created_at
                            ->toIso8601String(),
                    ],
                )
                ->values()
                ->all();

        return new MerchantFeedExportHistoryData(
            items: $items,

            pagination: [
                'current_page' => $currentPage,

                'per_page' => self::PER_PAGE,

                'total' => $total,

                'last_page' => $lastPage,

                'from' => $total === 0
                        ? null
                        : $offset + 1,

                'to' => $total === 0
                        ? null
                        : $offset
                            + count(
                                $items,
                            ),

                'previous_page' => $currentPage > 1
                        ? $currentPage - 1
                        : null,

                'next_page' => $currentPage < $lastPage
                        ? $currentPage + 1
                        : null,
            ],
        );
    }

    private static function canDownload(
        MerchantFeedExport $export,
    ): bool {
        return $export->status ===
                MerchantFeedExportStatus::Completed
            && $export->file_name !== null
            && $export->file_mime_type !== null
            && $export->file_size !== null
            && $export->file_sha256 !== null;
    }

    private static function canRetry(
        MerchantFeedExport $export,
    ): bool {
        return $export->status ===
                MerchantFeedExportStatus::Failed
            && $export->failure_code !==
                'snapshot_changed';
    }
}
