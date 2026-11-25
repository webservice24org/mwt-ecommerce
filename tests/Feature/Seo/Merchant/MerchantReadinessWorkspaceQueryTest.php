<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Domain\Seo\Merchant\Queries\GetMerchantReadinessWorkspaceQuery;
use App\Domain\Seo\Merchant\Services\MerchantProductDataService;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantReadinessWorkspaceQueryTest extends TestCase
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

    public function test_workspace_summarizes_ready_needs_work_and_blocked_items_from_m1_and_m3_contracts(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Acme',

                    'slug' => 'acme',
                ]);

        $ready =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Ready Product',

                    'slug' => 'ready-product',

                    'brand_id' => $brand->id,

                    'sku' => 'READY-001',

                    'price' => 85000,

                    'short_description' => 'A complete merchant product.',

                    'track_inventory' => true,

                    'stock_quantity' => 0,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $ready->id,

                'path' => 'products/ready.jpg',
            ]);

        app(
            MerchantProductDataService::class,
        )->save(
            product: $ready,

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

        $needsWork =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Needs Work Product',

                    'slug' => 'needs-work-product',

                    'brand_id' => null,

                    'sku' => 'WORK-001',

                    'price' => 90000,

                    'short_description' => 'This product deliberately has unresolved merchant identifiers.',

                    'track_inventory' => true,

                    'stock_quantity' => 5,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $needsWork->id,

                'path' => 'products/needs-work.jpg',
            ]);

        $blocked =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Blocked Product',

                    'slug' => 'blocked-product',

                    'brand_id' => $brand->id,

                    'sku' => 'BLOCKED-001',

                    'price' => 95000,

                    'short_description' => 'This product intentionally has no image.',

                    'track_inventory' => true,

                    'stock_quantity' => 2,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        app(
            MerchantProductDataService::class,
        )->save(
            product: $blocked,

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

        $workspace =
            app(
                GetMerchantReadinessWorkspaceQuery::class,
            )->get();

        self::assertSame(
            3,
            $workspace
                ->summary
                ->productsConsidered,
        );

        self::assertSame(
            3,
            $workspace
                ->summary
                ->itemCandidates,
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->ready,
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->needsWork,
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->blocked,
        );

        self::assertSame(
            2,
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

        self::assertSame(
            1,
            $workspace
                ->summary
                ->outOfStock,
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->findingCounts[
                    'primary_image_missing'
                ],
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->findingCounts[
                    'brand_missing_review_required'
                ],
        );

        self::assertSame(
            1,
            $workspace
                ->summary
                ->findingCounts[
                    'merchant_identifier_state_unknown'
                ],
        );

        $byProduct = [];

        foreach ($workspace->items as $item) {
            $byProduct[
                $item->productId
            ] =
                $item;
        }

        self::assertSame(
            'ready',
            $byProduct[
                $ready->id
            ]
                ->status
                ->value,
        );

        self::assertTrue(
            $byProduct[
                $ready->id
            ]
                ->feedEligible,
        );

        self::assertSame(
            'out_of_stock',
            $byProduct[
                $ready->id
            ]
                ->availability,
        );

        self::assertSame(
            'needs_work',
            $byProduct[
                $needsWork->id
            ]
                ->status
                ->value,
        );

        self::assertSame(
            'unknown',
            $byProduct[
                $needsWork->id
            ]
                ->identifierState,
        );

        self::assertSame(
            'blocked',
            $byProduct[
                $blocked->id
            ]
                ->status
                ->value,
        );

        self::assertFalse(
            $byProduct[
                $blocked->id
            ]
                ->feedEligible,
        );

        self::assertTrue(
            $workspace
                ->policy
                ->structuredDataEnabled,
        );

        self::assertFalse(
            $workspace
                ->policy
                ->shippingMarkupEnabled,
        );

        self::assertFalse(
            $workspace
                ->policy
                ->shippingPublished,
        );

        self::assertFalse(
            $workspace
                ->policy
                ->returnPolicyEnabled,
        );

        self::assertFalse(
            $workspace
                ->policy
                ->returnPolicyPublished,
        );
    }

    public function test_workspace_is_read_only_for_catalog_and_keeps_m3_feed_outputs_available(): void
    {
        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Read Only Product',

                    'slug' => 'read-only-product',

                    'sku' => 'READ-ONLY-001',

                    'price' => 70000,

                    'short_description' => 'Read-only merchant readiness candidate.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/read-only.jpg',
            ]);

        $beforeUpdatedAt =
            $product->updated_at;

        $workspace =
            app(
                GetMerchantReadinessWorkspaceQuery::class,
            )->get();

        self::assertCount(
            1,
            $workspace->items,
        );

        self::assertSame(
            'p-'
            .$product->id,
            $workspace
                ->items[
                    0
                ]
                    ->feedId,
        );

        self::assertDatabaseCount(
            'merchant_product_data',
            0,
        );

        self::assertSame(
            $beforeUpdatedAt
                ?->toDateTimeString(),
            $product
                ->fresh()
                ?->updated_at
                ?->toDateTimeString(),
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
