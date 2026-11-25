<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportLifecycle;
use App\Domain\Seo\Merchant\Services\MerchantProductDataService;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Jobs\Seo\Merchant\GenerateMerchantFeedExport;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantM4SecurityBoundaryTest extends TestCase
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

    public function test_snapshot_changed_export_cannot_be_retried_by_http_or_domain_caller(): void
    {
        Queue::fake();

        $product =
            $this->readyProduct();

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

        $product->forceFill([
            'price' => 99000,
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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.seo.merchant.exports.retry',
                    $export->public_id,
                ),
            )
            ->assertStatus(
                409,
            );

        Queue::assertNothingPushed();

        $this->expectException(
            DomainException::class,
        );

        $lifecycle->retry(
            export: $export,

            admin: $admin,
        );
    }

    public function test_tampered_completed_file_is_rejected_without_download_audit_event(): void
    {
        $this->readyProduct(
            'TAMPER',
        );

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

        $eventsBefore =
            $export
                ->events()
                ->count();

        $filePath =
            $export->file_path;

        self::assertNotNull(
            $filePath,
        );

        Storage::disk('local')
            ->put(
                $filePath,
                "tampered\n",
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

        self::assertSame(
            $eventsBefore,
            $export
                ->events()
                ->count(),
        );
    }

    public function test_internal_numeric_export_id_is_not_a_valid_download_route_identifier(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Editor,
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
                    (string) $export->id,
                ),
            )
            ->assertNotFound();
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
