<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Domain\Seo\Merchant\Services\MerchantProductFeedItemFactory;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MerchantProductFeedItemFactoryTest extends TestCase
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

    public function test_simple_product_maps_authoritative_price_availability_image_and_identifiers(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'MWT',
                ]);

        $product =
            Product::factory()
                ->create([
                    'brand_id' => $brand->id,

                    'type' => ProductType::Simple,

                    'name' => 'Braided USB Cable',

                    'slug' => 'braided-usb-cable',

                    'sku' => 'CABLE-001',

                    'price' => 85000,

                    'track_inventory' => true,

                    'stock_quantity' => 5,

                    'short_description' => 'Fast braided USB cable.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/cable.jpg',
            ]);

        $result =
            app(
                MerchantProductFeedItemFactory::class,
            )->build(
                product: $product,

                variant: null,

                metadata: $this->metadata(
                    gtin: '4006381333931',

                    mpn: 'MWT-CABLE-001',
                ),
            );

        self::assertNotNull(
            $result->item,
        );

        $item =
            $result->item;

        self::assertSame(
            'p-'
            .$product->id,
            $item->id,
        );

        self::assertSame(
            'Braided USB Cable',
            $item->title,
        );

        self::assertSame(
            'https://example.com/products/braided-usb-cable',
            $item->link,
        );

        self::assertSame(
            'https://example.com/storage/products/cable.jpg',
            $item->imageLink,
        );

        self::assertSame(
            '850.00 BDT',
            $item->price,
        );

        self::assertSame(
            'in_stock',
            $item->availability,
        );

        self::assertSame(
            'MWT',
            $item->brand,
        );

        self::assertSame(
            '4006381333931',
            $item->gtin,
        );

        self::assertSame(
            'MWT-CABLE-001',
            $item->mpn,
        );

        self::assertSame(
            'yes',
            $item->identifierExists,
        );

        self::assertSame(
            'new',
            $item->condition,
        );

        self::assertNull(
            $item->itemGroupId,
        );

        self::assertSame(
            [],
            $result->exclusionReasons,
        );
    }

    public function test_variable_product_exports_active_variant_with_group_id_and_supported_attributes(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'MWT',
                ]);

        $product =
            Product::factory()
                ->variable()
                ->create([
                    'brand_id' => $brand->id,

                    'name' => 'USB Cable',

                    'slug' => 'usb-cable',

                    'sku' => 'CABLE-GROUP',

                    'track_inventory' => true,

                    'stock_quantity' => 0,

                    'short_description' => 'Braided USB cable with color variants.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/variable-cable.jpg',
            ]);

        $color =
            ProductAttribute::factory()
                ->create([
                    'name' => 'Color',

                    'slug' => 'color',

                    'is_active' => true,
                ]);

        $black =
            AttributeValue::factory()
                ->create([
                    'attribute_id' => $color->id,

                    'name' => 'Black',

                    'slug' => 'black',

                    'is_active' => true,
                ]);

        $variant =
            ProductVariant::factory()
                ->create([
                    'product_id' => $product->id,

                    'sku' => 'CABLE-BLACK',

                    'name' => null,

                    'price' => 90000,

                    'stock_quantity' => 0,

                    'barcode' => '4006381333931',

                    'is_active' => true,

                    'is_default' => true,

                    'position' => 0,
                ]);

        $variant
            ->attributeValues()
            ->sync([
                $black->id,
            ]);

        $result =
            app(
                MerchantProductFeedItemFactory::class,
            )->build(
                product: $product,

                variant: $variant,

                metadata: $this->metadata(
                    gtin: null,

                    mpn: 'MWT-BLACK',

                    identifierState: MerchantIdentifierState::Available,
                ),
            );

        self::assertNotNull(
            $result->item,
        );

        $item =
            $result->item;

        self::assertSame(
            'v-'
            .$variant->id,
            $item->id,
        );

        self::assertSame(
            'p-'
            .$product->id,
            $item->itemGroupId,
        );

        self::assertSame(
            'USB Cable - Black',
            $item->title,
        );

        self::assertSame(
            'https://example.com/products/usb-cable?variant='
            .$variant->id,
            $item->link,
        );

        self::assertSame(
            '900.00 BDT',
            $item->price,
        );

        self::assertSame(
            'out_of_stock',
            $item->availability,
        );

        self::assertSame(
            'Black',
            $item->color,
        );

        /*
         * Existing generic barcode is deliberately not promoted to GTIN.
         * Only explicit merchant metadata may populate GTIN.
         */
        self::assertNull(
            $item->gtin,
        );

        self::assertSame(
            'MWT-BLACK',
            $item->mpn,
        );
    }

    public function test_product_blocked_by_m1_is_excluded_from_feed_item(): void
    {
        $brand =
            Brand::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'brand_id' => $brand->id,

                    'type' => ProductType::Simple,

                    'name' => 'No Image Product',

                    'slug' => 'no-image-product',

                    'sku' => 'NO-IMAGE',

                    'price' => 10000,

                    'short_description' => 'Product with no merchant image.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $result =
            app(
                MerchantProductFeedItemFactory::class,
            )->build(
                product: $product,

                variant: null,

                metadata: $this->metadata(
                    gtin: '4006381333931',
                ),
            );

        self::assertNull(
            $result->item,
        );

        self::assertContains(
            'primary_image_missing',
            $result
                ->exclusionReasons,
        );
    }

    public function test_used_or_refurbished_condition_is_not_exported_until_landing_page_can_show_it(): void
    {
        $product =
            Product::factory()
                ->create([
                    'type' => ProductType::Simple,

                    'name' => 'Used Product',

                    'slug' => 'used-product',

                    'sku' => 'USED-001',

                    'price' => 85000,

                    'short_description' => 'A product whose non-new condition must be visible before export.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/used.jpg',
            ]);

        $result =
            app(
                MerchantProductFeedItemFactory::class,
            )->build(
                product: $product,

                variant: null,

                metadata: new MerchantProductMetadataData(
                    condition: MerchantProductCondition::Used,

                    identifierState: MerchantIdentifierState::Unknown,

                    gtin: null,

                    mpn: null,

                    googleProductCategory: null,

                    version: 1,
                ),
            );

        self::assertNull(
            $result->item,
        );

        self::assertContains(
            'non_new_condition_not_visible_on_landing_page',
            $result
                ->exclusionReasons,
        );
    }

    private function metadata(
        ?string $gtin,
        ?string $mpn = null,
        MerchantIdentifierState $identifierState = MerchantIdentifierState::Available,
    ): MerchantProductMetadataData {
        return new MerchantProductMetadataData(
            condition: MerchantProductCondition::New,

            identifierState: $identifierState,

            gtin: $gtin,

            mpn: $mpn,

            googleProductCategory: null,

            version: 1,
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
