<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\StructuredData;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProductAvailabilityStructuredDataIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.currency' => 'BDT',

            'app.url' => 'https://example.com',
        ]);

        $this->saveSeoSettings(
            enabled: true,
        );
    }

    public function test_simple_tracked_product_with_stock_is_in_stock(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'In Stock Charger',

                    'slug' => 'in-stock-charger',

                    'sku' => 'CHG-IN',

                    'price' => 485000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),

                    'track_inventory' => true,

                    'stock_quantity' => 10,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'structuredData.@graph.1.@type',
                        'Product',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.availability',
                        'https://schema.org/InStock',
                    ),
            );
    }

    public function test_simple_tracked_product_without_stock_is_out_of_stock(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Out Of Stock Charger',

                    'slug' => 'out-of-stock-charger',

                    'sku' => 'CHG-OUT',

                    'price' => 485000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),

                    'track_inventory' => true,

                    'stock_quantity' => 0,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'structuredData.@graph.1.offers.availability',
                    'https://schema.org/OutOfStock',
                ),
            );
    }

    public function test_simple_untracked_product_is_in_stock_even_when_quantity_is_zero(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Untracked Charger',

                    'slug' => 'untracked-charger',

                    'sku' => 'CHG-UNTRACKED',

                    'price' => 485000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),

                    'track_inventory' => false,

                    'stock_quantity' => 0,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'structuredData.@graph.1.offers.availability',
                    'https://schema.org/InStock',
                ),
            );
    }

    public function test_variable_product_uses_each_active_variants_authoritative_stock(): void
    {
        $product =
            Product::factory()
                ->variable()
                ->create([
                    'name' => 'USB Cable',

                    'slug' => 'availability-cable',

                    'sku' => 'CABLE-GROUP',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),

                    'track_inventory' => true,

                    'stock_quantity' => 0,
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

        $white =
            AttributeValue::factory()
                ->create([
                    'attribute_id' => $color->id,

                    'name' => 'White',

                    'slug' => 'white',

                    'is_active' => true,
                ]);

        $inStock =
            ProductVariant::factory()
                ->create([
                    'product_id' => $product->id,

                    'sku' => 'CABLE-BLACK',

                    'name' => 'Black',

                    'price' => 85000,

                    'stock_quantity' => 5,

                    'is_active' => true,

                    'is_default' => true,

                    'position' => 0,
                ]);

        $outOfStock =
            ProductVariant::factory()
                ->create([
                    'product_id' => $product->id,

                    'sku' => 'CABLE-WHITE',

                    'name' => 'White',

                    'price' => 90000,

                    'stock_quantity' => 0,

                    'is_active' => true,

                    'is_default' => false,

                    'position' => 1,
                ]);

        $inactive =
            ProductVariant::factory()
                ->create([
                    'product_id' => $product->id,

                    'sku' => 'CABLE-INACTIVE',

                    'name' => 'Inactive',

                    'price' => 95000,

                    'stock_quantity' => 10,

                    'is_active' => false,

                    'is_default' => false,

                    'position' => 2,
                ]);

        $inStock
            ->attributeValues()
            ->sync([
                $black->id,
            ]);

        $outOfStock
            ->attributeValues()
            ->sync([
                $white->id,
            ]);

        $inactive
            ->attributeValues()
            ->sync([
                $black->id,
            ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'structuredData.@graph.1.@type',
                        'ProductGroup',
                    )
                    ->where(
                        'structuredData.@graph.1.productGroupID',
                        'CABLE-GROUP',
                    )
                    ->has(
                        'structuredData.@graph.1.hasVariant',
                        2,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.sku',
                        'CABLE-BLACK',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.color',
                        'Black',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.offers.availability',
                        'https://schema.org/InStock',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.sku',
                        'CABLE-WHITE',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.color',
                        'White',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.offers.availability',
                        'https://schema.org/OutOfStock',
                    ),
            );
    }

    public function test_untracked_variable_product_marks_zero_quantity_variant_in_stock(): void
    {
        $product =
            Product::factory()
                ->variable()
                ->create([
                    'name' => 'Untracked Cable',

                    'slug' => 'untracked-cable',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),

                    'track_inventory' => false,

                    'stock_quantity' => 0,
                ]);

        $variant =
            ProductVariant::factory()
                ->create([
                    'product_id' => $product->id,

                    'sku' => 'UNTRACKED-VARIANT',

                    'price' => 85000,

                    'stock_quantity' => 0,

                    'is_active' => true,

                    'is_default' => true,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug
                .'?variant='
                .$variant->id,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'selectedVariantId',
                        $variant->id,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.offers.availability',
                        'https://schema.org/InStock',
                    ),
            );
    }

    public function test_global_structured_data_disable_returns_null_for_product_page(): void
    {
        $this->saveSeoSettings(
            enabled: false,
        );

        $product =
            Product::factory()
                ->create([
                    'name' => 'Disabled Schema Product',

                    'slug' => 'disabled-schema-product',

                    'sku' => 'DISABLED-SCHEMA',

                    'price' => 485000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'structuredData',
                    null,
                ),
            );
    }

    private function saveSeoSettings(
        bool $enabled,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: null,

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

                enableStructuredData: $enabled,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
