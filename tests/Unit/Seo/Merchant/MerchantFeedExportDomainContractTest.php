<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantFeedExportSnapshotData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportFormat;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use PHPUnit\Framework\TestCase;

final class MerchantFeedExportDomainContractTest extends TestCase
{
    public function test_export_status_contract_is_explicit_and_closed(): void
    {
        self::assertSame(
            [
                'queued',
                'processing',
                'completed',
                'failed',
            ],
            array_map(
                static fn (
                    MerchantFeedExportStatus $status,
                ): string => $status->value,
                MerchantFeedExportStatus::cases(),
            ),
        );
    }

    public function test_m4c_supports_only_the_existing_m3_tsv_feed_format(): void
    {
        self::assertSame(
            ['tsv'],
            array_map(
                static fn (
                    MerchantFeedExportFormat $format,
                ): string => $format->value,
                MerchantFeedExportFormat::cases(),
            ),
        );

        self::assertSame(
            'merchant-tsv-v1',
            MerchantFeedExportSnapshotData::SCHEMA_VERSION,
        );
    }

    public function test_snapshot_hash_is_stable_for_identical_manifest(): void
    {
        $snapshot = [
            'format' => 'tsv',
            'eligible_feed_ids' => [
                'p-1',
                'v-2',
            ],
        ];

        self::assertSame(
            MerchantFeedExportSnapshotData::hash(
                $snapshot,
            ),
            MerchantFeedExportSnapshotData::hash(
                $snapshot,
            ),
        );
    }
}
