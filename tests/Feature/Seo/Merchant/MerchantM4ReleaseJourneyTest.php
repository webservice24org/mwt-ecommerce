<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportEventType;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Domain\Seo\Merchant\Queries\GetMerchantReadinessWorkspaceQuery;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportLifecycle;
use App\Domain\Seo\Merchant\Services\MerchantProductDataService;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Jobs\Seo\Merchant\GenerateMerchantFeedExport;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\MerchantFeedExportEvent;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantM4ReleaseJourneyTest extends TestCase
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

    public function test_readiness_snapshot_export_file_and_secure_download_remain_consistent_end_to_end(): void
    {
        $ready =
            $this->readyProduct();

        $blocked =
            $this->blockedProduct();

        $workspace =
            app(
                GetMerchantReadinessWorkspaceQuery::class,
            )->get();

        self::assertSame(
            2,
            $workspace
                ->summary
                ->productsConsidered,
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->feedEligible,
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->excluded,
        );

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

        self::assertSame(
            $workspace
                ->summary
                ->feedEligible,
            $export->exportable_count,
        );

        self::assertSame(
            $workspace
                ->summary
                ->excluded,
            $export->excluded_count,
        );

        /** @var list<string> $eligibleFeedIds */
        $eligibleFeedIds =
            $export->snapshot[
                'eligible_feed_ids'
            ];

        self::assertContains(
            'p-'.$ready->id,
            $eligibleFeedIds,
        );

        /** @var list<array{product_id: int, variant_id: int|null, finding_ids: list<string>}> $excludedItems */
        $excludedItems =
            $export->snapshot[
                'excluded_items'
            ];

        $blockedFound = false;

        foreach ($excludedItems as $item) {
            if (
                $item['product_id'] ===
                $blocked->id
            ) {
                $blockedFound = true;
                break;
            }
        }

        self::assertTrue(
            $blockedFound,
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

        $filePath =
            $export->file_path;

        self::assertNotNull(
            $filePath,
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

        self::assertStringContainsString(
            "id\ttitle\tdescription\tlink\timage_link",
            $contents,
        );

        self::assertStringContainsString(
            "\np-{$ready->id}\t",
            $contents,
        );

        self::assertStringNotContainsString(
            "\np-{$blocked->id}\t",
            $contents,
        );

        self::assertSame(
            hash(
                'sha256',
                $contents,
            ),
            $export->file_sha256,
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
            ->assertOk()
            ->assertDownload(
                (string) $export->file_name,
            );

        self::assertTrue(
            $export
                ->events()
                ->get()
                ->contains(
                    static fn (
                        MerchantFeedExportEvent $event,
                    ): bool => $event->event_type ===
                        MerchantFeedExportEventType::DownloadRequested,
                ),
        );
    }

    private function readyProduct(
        string $suffix = '001',
    ): Product {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Closure Brand '.$suffix,

                    'slug' => 'closure-brand-'.strtolower($suffix),
                ]);

        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Closure Ready Product '.$suffix,

                    'slug' => 'closure-ready-product-'.strtolower($suffix),

                    'brand_id' => $brand->id,

                    'sku' => 'CLOSURE-'.$suffix,

                    'price' => 85000,

                    'short_description' => 'Complete merchant product used by the M.4F release closure suite.',

                    'track_inventory' => true,

                    'stock_quantity' => 4,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/closure-ready-'.strtolower($suffix).'.jpg',
            ]);

        $this->declareIdentifiersUnavailable(
            $product,
        );

        return $product;
    }

    private function blockedProduct(): Product
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Blocked Closure Brand',

                    'slug' => 'blocked-closure-brand',
                ]);

        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Closure Blocked Product',

                    'slug' => 'closure-blocked-product',

                    'brand_id' => $brand->id,

                    'sku' => 'CLOSURE-BLOCKED',

                    'price' => 92000,

                    'short_description' => 'Published product intentionally missing a primary image.',

                    'track_inventory' => true,

                    'stock_quantity' => 2,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $this->declareIdentifiersUnavailable(
            $product,
        );

        return $product;
    }

    private function declareIdentifiersUnavailable(
        Product $product,
    ): void {
        app(
            MerchantProductDataService::class,
        )->save(
            product: $product,

            variant: null,

            data: new MerchantProductMetadataData(
                condition: MerchantProductCondition::New,

                identifierState: MerchantIdentifierState::NotAvailableDeclared,

                gtin: null,

                mpn: null,

                googleProductCategory: null,

                version: 0,
            ),

            expectedVersion: 0,
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
