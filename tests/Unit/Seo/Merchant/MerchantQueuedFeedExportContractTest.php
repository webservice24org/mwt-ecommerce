<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantFeedExportSnapshotData;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportGenerator;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportQueue;
use App\Jobs\Seo\Merchant\GenerateMerchantFeedExport;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionClass;
use Tests\TestCase;

#[CoversNothing]
final class MerchantQueuedFeedExportContractTest extends TestCase
{
    public function test_job_contract_is_queued_unique_and_bounded(): void
    {
        $job =
            new GenerateMerchantFeedExport(
                123,
            );

        self::assertInstanceOf(
            ShouldQueue::class,
            $job,
        );

        self::assertInstanceOf(
            ShouldBeUnique::class,
            $job,
        );

        self::assertSame(
            3,
            $job->tries,
        );

        self::assertSame(
            120,
            $job->timeout,
        );

        self::assertSame(
            [10, 60, 300],
            $job->backoff(),
        );
    }

    public function test_export_services_are_final_and_snapshot_schema_remains_stable(): void
    {
        self::assertTrue(
            (new ReflectionClass(
                MerchantFeedExportGenerator::class,
            ))->isFinal(),
        );

        self::assertTrue(
            (new ReflectionClass(
                MerchantFeedExportQueue::class,
            ))->isFinal(),
        );

        self::assertSame(
            'merchant-tsv-v1',
            MerchantFeedExportSnapshotData::SCHEMA_VERSION,
        );
    }

    public function test_default_export_storage_is_private_local_disk(): void
    {
        self::assertSame(
            'local',
            config(
                'merchant-feed.exports.disk',
            ),
        );

        self::assertSame(
            'merchant-feed-exports',
            config(
                'merchant-feed.exports.directory',
            ),
        );
    }
}
