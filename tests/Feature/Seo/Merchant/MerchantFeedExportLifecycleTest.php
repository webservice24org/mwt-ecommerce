<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Data\MerchantFeedExportFileData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportEventType;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportLifecycle;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Admin;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MerchantFeedExportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://example.com',

            'commerce.currency' => 'BDT',
        ]);

        $this->saveSeoSettings();
    }

    public function test_admin_can_request_start_and_complete_export_with_auditable_snapshot(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $lifecycle =
            app(
                MerchantFeedExportLifecycle::class,
            );

        $export =
            $lifecycle->request(
                $admin,
            );

        self::assertSame(
            MerchantFeedExportStatus::Queued,
            $export->status,
        );

        self::assertSame(
            $admin->id,
            $export->requested_by_admin_id,
        );

        self::assertSame(
            $admin->email,
            $export->requested_by_email,
        );

        self::assertSame(
            64,
            strlen(
                $export->snapshot_hash,
            ),
        );

        self::assertSame(
            0,
            $export->attempt_count,
        );

        self::assertSame(
            1,
            $export->events()->count(),
        );

        self::assertSame(
            MerchantFeedExportEventType::Requested,
            $export->events()->firstOrFail()->event_type,
        );

        $processing =
            $lifecycle->start(
                $export,
            );

        self::assertSame(
            MerchantFeedExportStatus::Processing,
            $processing->status,
        );

        self::assertSame(
            1,
            $processing->attempt_count,
        );

        $file =
            new MerchantFeedExportFileData(
                disk: 'local',

                path: 'merchant/exports/feed.tsv',

                name: 'merchant-feed.tsv',

                mimeType: 'text/tab-separated-values',

                size: 1234,

                sha256: str_repeat(
                    'a',
                    64,
                ),
            );

        $completed =
            $lifecycle->complete(
                $processing,
                $file,
            );

        self::assertSame(
            MerchantFeedExportStatus::Completed,
            $completed->status,
        );

        self::assertSame(
            1234,
            $completed->file_size,
        );

        self::assertSame(
            str_repeat('a', 64),
            $completed->file_sha256,
        );

        self::assertNotNull(
            $completed->completed_at,
        );

        self::assertSame(
            3,
            $completed->events()->count(),
        );

        $replayed =
            $lifecycle->complete(
                $completed,
                $file,
            );

        self::assertSame(
            $completed->id,
            $replayed->id,
        );

        self::assertSame(
            3,
            $replayed->events()->count(),
        );
    }

    public function test_failed_export_can_be_requeued_without_resetting_attempt_history(): void
    {
        $requester =
            Admin::factory()
                ->create();

        $retryingAdmin =
            Admin::factory()
                ->create();

        $lifecycle =
            app(
                MerchantFeedExportLifecycle::class,
            );

        $export =
            $lifecycle->start(
                $lifecycle->request(
                    $requester,
                ),
            );

        $failed =
            $lifecycle->fail(
                export: $export,

                failureCode: 'storage_write_failed',

                failureMessage: 'The export file could not be written.',
            );

        self::assertSame(
            MerchantFeedExportStatus::Failed,
            $failed->status,
        );

        self::assertSame(
            1,
            $failed->attempt_count,
        );

        $queued =
            $lifecycle->retry(
                $failed,
                $retryingAdmin,
            );

        self::assertSame(
            MerchantFeedExportStatus::Queued,
            $queued->status,
        );

        self::assertSame(
            1,
            $queued->attempt_count,
        );

        self::assertNull(
            $queued->failure_code,
        );

        self::assertNull(
            $queued->failed_at,
        );

        $retryEvent =
            $queued->events()
                ->where(
                    'event_type',
                    MerchantFeedExportEventType::Retried->value,
                )
                ->firstOrFail();

        self::assertSame(
            $retryingAdmin->id,
            $retryEvent->actor_admin_id,
        );

        $secondAttempt =
            $lifecycle->start(
                $queued,
            );

        self::assertSame(
            2,
            $secondAttempt->attempt_count,
        );
    }

    public function test_queued_export_cannot_complete_without_processing(): void
    {
        $export =
            app(
                MerchantFeedExportLifecycle::class,
            )->request(
                Admin::factory()
                    ->create(),
            );

        $this->expectException(
            DomainException::class,
        );

        app(
            MerchantFeedExportLifecycle::class,
        )->complete(
            $export,
            new MerchantFeedExportFileData(
                disk: 'local',

                path: 'merchant/exports/feed.tsv',

                name: 'merchant-feed.tsv',

                mimeType: 'text/tab-separated-values',

                size: 10,

                sha256: str_repeat(
                    'b',
                    64,
                ),
            ),
        );
    }

    public function test_equivalent_requests_produce_same_snapshot_hash_but_distinct_public_ids(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $lifecycle =
            app(
                MerchantFeedExportLifecycle::class,
            );

        $first =
            $lifecycle->request(
                $admin,
            );

        $second =
            $lifecycle->request(
                $admin,
            );

        self::assertSame(
            $first->snapshot_hash,
            $second->snapshot_hash,
        );

        self::assertNotSame(
            $first->public_id,
            $second->public_id,
        );
    }

    private function saveSeoSettings(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default store description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: null,

                twitterCard: 'summary_large_image',

                organizationName: 'MWT Store',

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
