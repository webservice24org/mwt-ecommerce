<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Seo\Merchant\Enums\MerchantFeedExportFormat;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Queries\GetMerchantFeedExportHistoryQuery;
use App\Models\Admin;
use App\Models\MerchantFeedExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MerchantFeedExportHistoryQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_is_newest_first_bounded_and_does_not_expose_storage_path(): void
    {
        $admin =
            Admin::factory()
                ->create();

        for ($index = 1; $index <= 12; $index++) {
            $this->export(
                admin: $admin,

                suffix: (string) $index,

                status: MerchantFeedExportStatus::Queued,
            );
        }

        $history =
            app(
                GetMerchantFeedExportHistoryQuery::class,
            )->get();

        self::assertCount(
            10,
            $history->items,
        );

        self::assertSame(
            12,
            $history->pagination['total'],
        );

        self::assertSame(
            2,
            $history->pagination['last_page'],
        );

        self::assertArrayNotHasKey(
            'file_path',
            $history->items[0],
        );

        self::assertArrayNotHasKey(
            'file_disk',
            $history->items[0],
        );
    }

    public function test_completed_export_is_downloadable_and_snapshot_changed_failure_is_not_offered_for_retry(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $completed =
            $this->export(
                admin: $admin,

                suffix: 'completed',

                status: MerchantFeedExportStatus::Completed,

                file: true,
            );

        $failed =
            $this->export(
                admin: $admin,

                suffix: 'failed',

                status: MerchantFeedExportStatus::Failed,

                failureCode: 'snapshot_changed',
            );

        $items =
            collect(
                app(
                    GetMerchantFeedExportHistoryQuery::class,
                )->get()
                    ->items,
            )->keyBy(
                'public_id',
            );

        self::assertTrue(
            (bool) $items[$completed->public_id]['can_download'],
        );

        self::assertFalse(
            (bool) $items[$failed->public_id]['can_retry'],
        );
    }

    private function export(
        Admin $admin,
        string $suffix,
        MerchantFeedExportStatus $status,
        bool $file = false,
        ?string $failureCode = null,
    ): MerchantFeedExport {
        $publicId =
            sprintf(
                '00000000-0000-4000-8000-%012d',
                abs(
                    crc32(
                        $suffix,
                    ),
                ) % 1000000000000,
            );

        return MerchantFeedExport::query()
            ->create([
                'public_id' => $publicId,

                'requested_by_admin_id' => $admin->id,

                'requested_by_name' => $admin->name,

                'requested_by_email' => $admin->email,

                'status' => $status,

                'format' => MerchantFeedExportFormat::Tsv,

                'schema_version' => 'merchant-tsv-v1',

                'readiness_policy_version' => 'merchant-listing-v1',

                'merchant_policy_version' => 1,

                'snapshot_hash' => str_repeat(
                    'a',
                    64,
                ),

                'snapshot' => [],

                'products_considered' => 2,

                'item_candidates' => 2,

                'exportable_count' => 1,

                'excluded_count' => 1,

                'out_of_stock_count' => 0,

                'blocker_count' => 1,

                'warning_count' => 0,

                'info_count' => 0,

                'truncated' => false,

                'max_items' => 5000,

                'attempt_count' => $status ===
                    MerchantFeedExportStatus::Queued
                        ? 0
                        : 1,

                'file_disk' => $file
                        ? 'local'
                        : null,

                'file_path' => $file
                        ? 'merchant-feed-exports/'
                            .$publicId
                            .'/merchant-products.tsv'
                        : null,

                'file_name' => $file
                        ? 'merchant-products-'
                            .$publicId
                            .'.tsv'
                        : null,

                'file_mime_type' => $file
                        ? 'text/tab-separated-values'
                        : null,

                'file_size' => $file
                        ? 123
                        : null,

                'file_sha256' => $file
                        ? str_repeat(
                            'b',
                            64,
                        )
                        : null,

                'failure_code' => $failureCode,

                'failure_message' => $failureCode === null
                        ? null
                        : 'Failure message.',

                'queued_at' => now(),

                'started_at' => $status ===
                    MerchantFeedExportStatus::Queued
                        ? null
                        : now(),

                'completed_at' => $status ===
                    MerchantFeedExportStatus::Completed
                        ? now()
                        : null,

                'failed_at' => $status ===
                    MerchantFeedExportStatus::Failed
                        ? now()
                        : null,
            ]);
    }
}
