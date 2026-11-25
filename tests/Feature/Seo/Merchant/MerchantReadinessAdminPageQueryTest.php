<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Domain\Seo\Merchant\Queries\GetMerchantReadinessAdminPageQuery;
use App\Domain\Seo\Merchant\Services\MerchantProductDataService;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantReadinessAdminPageQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://example.com',

            'commerce.currency' => 'BDT',
        ]);

        Storage::fake(
            'public',
        );

        $this->saveSeoSettings();
    }

    public function test_admin_page_query_filters_existing_m4a_truth_without_changing_overall_summary(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Acme',

                    'slug' => 'acme',
                ]);

        $ready =
            $this->createReadyProduct(
                $brand,
                'Ready Camera',
                'ready-camera',
                'READY-CAMERA',
            );

        $needsWork =
            $this->createNeedsWorkProduct(
                'Needs Work Lens',
                'needs-work-lens',
                'WORK-LENS',
            );

        $blocked =
            $this->createBlockedProduct(
                $brand,
                'Blocked Tripod',
                'blocked-tripod',
                'BLOCKED-TRIPOD',
            );

        $query =
            app(
                GetMerchantReadinessAdminPageQuery::class,
            );

        $blockedPage =
            $query->get(
                status: MerchantListingReadinessStatus::Blocked,
            );

        self::assertSame(
            3,
            $blockedPage
                ->summary
                ->itemCandidates,
        );

        self::assertSame(
            1,
            $blockedPage
                ->pagination[
                    'total'
                ],
        );

        self::assertCount(
            1,
            $blockedPage->items,
        );

        self::assertSame(
            $blocked->id,
            $blockedPage
                ->items[0]
                ->productId,
        );

        $warningPage =
            $query->get(
                severity: MerchantListingFindingSeverity::Warning,
            );

        self::assertSame(
            1,
            $warningPage
                ->pagination[
                    'total'
                ],
        );

        self::assertSame(
            $needsWork->id,
            $warningPage
                ->items[0]
                ->productId,
        );

        $eligibleSearch =
            $query->get(
                search: 'Ready Camera',

                feed: 'eligible',
            );

        self::assertSame(
            1,
            $eligibleSearch
                ->pagination[
                    'total'
                ],
        );

        self::assertSame(
            $ready->id,
            $eligibleSearch
                ->items[0]
                ->productId,
        );

        self::assertTrue(
            $eligibleSearch
                ->items[0]
                ->feedEligible,
        );

        $excluded =
            $query->get(
                feed: 'excluded',
            );

        self::assertSame(
            1,
            $excluded
                ->pagination[
                    'total'
                ],
        );

        self::assertSame(
            $blocked->id,
            $excluded
                ->items[0]
                ->productId,
        );
    }

    public function test_admin_page_query_uses_bounded_fifty_item_pages(): void
    {
        $brand =
            Brand::factory()
                ->create();

        foreach (
            range(
                1,
                51,
            ) as $index
        ) {
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => sprintf(
                        'Blocked Product %02d',
                        $index,
                    ),

                    'slug' => sprintf(
                        'blocked-product-%02d',
                        $index,
                    ),

                    'brand_id' => $brand->id,

                    'sku' => sprintf(
                        'BLOCK-%02d',
                        $index,
                    ),

                    'price' => 50000 + $index,

                    'short_description' => 'Published product deliberately missing a primary image.',

                    'track_inventory' => true,

                    'stock_quantity' => 3,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);
        }

        $page =
            app(
                GetMerchantReadinessAdminPageQuery::class,
            )->get(
                status: MerchantListingReadinessStatus::Blocked,

                page: 2,
            );

        self::assertSame(
            51,
            $page
                ->pagination[
                    'total'
                ],
        );

        self::assertSame(
            2,
            $page
                ->pagination[
                    'current_page'
                ],
        );

        self::assertSame(
            50,
            $page
                ->pagination[
                    'per_page'
                ],
        );

        self::assertSame(
            51,
            $page
                ->pagination[
                    'from'
                ],
        );

        self::assertSame(
            51,
            $page
                ->pagination[
                    'to'
                ],
        );

        self::assertNull(
            $page
                ->pagination[
                    'next_page'
                ],
        );

        self::assertSame(
            1,
            $page
                ->pagination[
                    'previous_page'
                ],
        );

        self::assertCount(
            1,
            $page->items,
        );
    }

    private function createReadyProduct(
        Brand $brand,
        string $name,
        string $slug,
        string $sku,
    ): Product {
        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => $name,

                    'slug' => $slug,

                    'brand_id' => $brand->id,

                    'sku' => $sku,

                    'price' => 85000,

                    'short_description' => 'A complete merchant product.',

                    'track_inventory' => true,

                    'stock_quantity' => 5,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/'
                    .$slug
                    .'.jpg',
            ]);

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

        return $product;
    }

    private function createNeedsWorkProduct(
        string $name,
        string $slug,
        string $sku,
    ): Product {
        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => $name,

                    'slug' => $slug,

                    'brand_id' => null,

                    'sku' => $sku,

                    'price' => 90000,

                    'short_description' => 'This product has unresolved merchant identifiers.',

                    'track_inventory' => true,

                    'stock_quantity' => 5,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/'
                    .$slug
                    .'.jpg',
            ]);

        return $product;
    }

    private function createBlockedProduct(
        Brand $brand,
        string $name,
        string $slug,
        string $sku,
    ): Product {
        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => $name,

                    'slug' => $slug,

                    'brand_id' => $brand->id,

                    'sku' => $sku,

                    'price' => 95000,

                    'short_description' => 'This product deliberately has no image.',

                    'track_inventory' => true,

                    'stock_quantity' => 2,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

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
