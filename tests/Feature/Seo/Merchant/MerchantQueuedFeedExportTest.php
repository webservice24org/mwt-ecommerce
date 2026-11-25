<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportEventType;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportGenerator;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportLifecycle;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportQueue;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Jobs\Seo\Merchant\GenerateMerchantFeedExport;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantQueuedFeedExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://example.com',

            'commerce.currency' => 'BDT',

            'merchant-feed.exports.disk' => 'local',

            'merchant-feed.exports.directory' => 'merchant-feed-exports',

            'merchant-feed.exports.queue' => 'merchant-exports',
        ]);

        Storage::fake(
            'public',
        );

        Storage::fake(
            'local',
        );

        $this->saveSeoSettings();
    }

    public function test_request_and_dispatch_creates_queued_export_and_queues_unique_job(): void
    {
        Queue::fake();

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $export =
            app(
                MerchantFeedExportQueue::class,
            )->requestAndDispatch(
                $admin,
            );

        self::assertSame(
            MerchantFeedExportStatus::Queued,
            $export->status,
        );

        Queue::assertPushedOn(
            'merchant-exports',
            GenerateMerchantFeedExport::class,
        );

        Queue::assertPushed(
            GenerateMerchantFeedExport::class,
            static fn (
                GenerateMerchantFeedExport $job,
            ): bool => $job->exportId ===
                $export->id,
        );
    }

    public function test_job_generates_private_tsv_and_completes_export(): void
    {
        $product =
            $this->readyProduct();

        $export =
            app(
                MerchantFeedExportLifecycle::class,
            )->request(
                Admin::factory()
                    ->create(),
            );

        $job =
            new GenerateMerchantFeedExport(
                $export->id,
            );

        app()->call([
            $job,
            'handle',
        ]);

        $export->refresh();

        self::assertSame(
            MerchantFeedExportStatus::Completed,
            $export->status,
        );

        self::assertSame(
            1,
            $export->attempt_count,
        );

        $filePath =
            $export->file_path;

        self::assertNotNull(
            $filePath,
        );

        self::assertSame(
            'local',
            $export->file_disk,
        );

        Storage::disk('local')
            ->assertExists(
                $filePath,
            );

        $contents =
            Storage::disk('local')
                ->get(
                    $filePath,
                );

        self::assertIsString(
            $contents,
        );

        self::assertStringStartsWith(
            "id\ttitle\tdescription\tlink\timage_link",
            $contents,
        );

        self::assertStringContainsString(
            'p-'.$product->id,
            $contents,
        );

        self::assertSame(
            hash(
                'sha256',
                $contents,
            ),
            $export->file_sha256,
        );

        self::assertSame(
            [
                MerchantFeedExportEventType::Requested,
                MerchantFeedExportEventType::Started,
                MerchantFeedExportEventType::Completed,
            ],
            $export->events()
                ->get()
                ->pluck('event_type')
                ->all(),
        );
    }

    public function test_changed_feed_content_fails_closed_instead_of_exporting_newer_catalog_under_old_snapshot(): void
    {
        $product =
            $this->readyProduct();

        $export =
            app(
                MerchantFeedExportLifecycle::class,
            )->request(
                Admin::factory()
                    ->create(),
            );

        $product->forceFill([
            'price' => 125000,
        ])->save();

        $job =
            new GenerateMerchantFeedExport(
                $export->id,
            );

        app()->call([
            $job,
            'handle',
        ]);

        $export->refresh();

        self::assertSame(
            MerchantFeedExportStatus::Failed,
            $export->status,
        );

        self::assertSame(
            'snapshot_changed',
            $export->failure_code,
        );

        self::assertNull(
            $export->file_path,
        );

        self::assertSame(
            [],
            Storage::disk('local')
                ->allFiles(
                    'merchant-feed-exports',
                ),
        );
    }

    public function test_generator_reuses_matching_final_file_after_interrupted_database_completion(): void
    {
        $this->readyProduct();

        $lifecycle =
            app(
                MerchantFeedExportLifecycle::class,
            );

        $export =
            $lifecycle->start(
                $lifecycle->request(
                    Admin::factory()
                        ->create(),
                ),
            );

        $generator =
            app(
                MerchantFeedExportGenerator::class,
            );

        $first =
            $generator->generate(
                $export,
            );

        $second =
            $generator->generate(
                $export,
            );

        self::assertSame(
            $first->path,
            $second->path,
        );

        self::assertSame(
            $first->sha256,
            $second->sha256,
        );

        $completed =
            $lifecycle->complete(
                $export,
                $second,
            );

        self::assertSame(
            MerchantFeedExportStatus::Completed,
            $completed->status,
        );
    }

    public function test_completed_job_replay_is_a_no_op(): void
    {
        $this->readyProduct();

        $export =
            app(
                MerchantFeedExportLifecycle::class,
            )->request(
                Admin::factory()
                    ->create(),
            );

        $job =
            new GenerateMerchantFeedExport(
                $export->id,
            );

        app()->call([
            $job,
            'handle',
        ]);

        app()->call([
            $job,
            'handle',
        ]);

        $export->refresh();

        self::assertSame(
            MerchantFeedExportStatus::Completed,
            $export->status,
        );

        self::assertSame(
            1,
            $export->attempt_count,
        );

        self::assertSame(
            3,
            $export->events()->count(),
        );
    }

    private function readyProduct(): Product
    {
        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Queued Export Product',

                    'slug' => 'queued-export-product',

                    'sku' => 'QUEUE-001',

                    'price' => 85000,

                    'short_description' => 'Complete product used by the queued merchant export test.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/queued-export.jpg',
            ]);

        return $product;
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
