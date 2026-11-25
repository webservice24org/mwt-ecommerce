<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportEventType;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportLifecycle;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Jobs\Seo\Merchant\GenerateMerchantFeedExport;
use App\Models\Admin;
use App\Models\MerchantFeedExport;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantFeedExportOperationsControllerTest extends TestCase
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

    public function test_authorized_editor_can_request_queued_export_from_workspace(): void
    {
        Queue::fake();

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Editor,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.seo.merchant.exports.store',
                ),
            )
            ->assertRedirect();

        $export =
            MerchantFeedExport::query()
                ->latest('id')
                ->firstOrFail();

        self::assertSame(
            MerchantFeedExportStatus::Queued,
            $export->status,
        );

        Queue::assertPushedOn(
            'merchant-exports',
            GenerateMerchantFeedExport::class,
        );
    }

    public function test_failed_export_can_be_retried_and_dispatched(): void
    {
        Queue::fake();

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

        $failed =
            $lifecycle->fail(
                export: $export,

                failureCode: 'test_failure',

                failureMessage: 'Safe test failure.',
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.seo.merchant.exports.retry',
                    $failed->public_id,
                ),
            )
            ->assertRedirect();

        $failed->refresh();

        self::assertSame(
            MerchantFeedExportStatus::Queued,
            $failed->status,
        );

        Queue::assertPushed(
            GenerateMerchantFeedExport::class,
        );
    }

    public function test_completed_export_download_is_private_integrity_checked_and_audited(): void
    {
        $this->readyProduct();

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Manager,
                ]);

        $export =
            app(
                MerchantFeedExportLifecycle::class,
            )->request(
                $admin,
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

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.seo.merchant.exports.download',
                        $export->public_id,
                    ),
                );

        $response
            ->assertOk()
            ->assertDownload(
                (string) $export->file_name,
            );

        $cacheControl =
            (string) $response
                ->headers
                ->get('cache-control');

        $cacheDirectives =
            array_map(
                static fn (
                    string $directive,
                ): string => trim(
                    strtolower($directive),
                ),
                explode(
                    ',',
                    $cacheControl,
                ),
            );

        self::assertContains(
            'private',
            $cacheDirectives,
        );

        self::assertContains(
            'no-store',
            $cacheDirectives,
        );

        self::assertContains(
            'max-age=0',
            $cacheDirectives,
        );

        self::assertSame(
            MerchantFeedExportEventType::DownloadRequested,
            $export
                ->events()
                ->reorder('id', 'desc')
                ->firstOrFail()
                ->event_type,
        );
    }

    public function test_non_completed_export_cannot_be_downloaded(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $export =
            app(
                MerchantFeedExportLifecycle::class,
            )->request(
                $admin,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.seo.merchant.exports.download',
                    $export->public_id,
                ),
            )
            ->assertStatus(
                409,
            );
    }

    public function test_guest_cannot_request_or_download_merchant_exports(): void
    {
        $this
            ->post(
                route(
                    'admin.seo.merchant.exports.store',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    private function readyProduct(): Product
    {
        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'M4E Download Product',

                    'slug' => 'm4e-download-product',

                    'sku' => 'M4E-001',

                    'price' => 85000,

                    'short_description' => 'Complete product used by the M.4E secure download test.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/m4e-download.jpg',
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
