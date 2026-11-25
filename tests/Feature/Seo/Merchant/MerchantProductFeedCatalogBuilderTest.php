<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Services\MerchantProductFeedCatalogBuilder;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantProductFeedCatalogBuilderTest extends TestCase
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

    public function test_catalog_includes_publishable_items_and_reports_blocked_items_without_mutating_catalog(): void
    {
        $included =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Included Product',

                    'slug' => 'included-product',

                    'sku' => 'INCLUDED-001',

                    'price' => 85000,

                    'short_description' => 'A complete product for merchant export.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $included->id,

                'path' => 'products/included.jpg',
            ]);

        $excluded =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Excluded Product',

                    'slug' => 'excluded-product',

                    'sku' => 'EXCLUDED-001',

                    'price' => 90000,

                    'short_description' => 'This item intentionally has no image.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $catalog =
            app(
                MerchantProductFeedCatalogBuilder::class,
            )->build();

        self::assertCount(
            1,
            $catalog->items,
        );

        self::assertSame(
            'p-'
            .$included->id,
            $catalog
                ->items[
                    0
                ]
                    ->id,
        );

        self::assertCount(
            1,
            $catalog->excluded,
        );

        self::assertSame(
            $excluded->id,
            $catalog
                ->excluded[
                    0
                ][
                    'product_id'
                ],
        );

        self::assertContains(
            'primary_image_missing',
            $catalog
                ->excluded[
                    0
                ][
                    'reasons'
                ],
        );

        self::assertFalse(
            $catalog->truncated,
        );

        self::assertDatabaseCount(
            'merchant_product_data',
            0,
        );
    }

    public function test_draft_product_is_not_considered_for_feed_catalog(): void
    {
        Product::factory()
            ->create([
                'name' => 'Draft Product',

                'slug' => 'draft-product',

                'price' => 85000,

                'status' => ProductStatus::Draft,
            ]);

        $catalog =
            app(
                MerchantProductFeedCatalogBuilder::class,
            )->build();

        self::assertSame(
            [],
            $catalog->items,
        );

        self::assertSame(
            [],
            $catalog->excluded,
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
