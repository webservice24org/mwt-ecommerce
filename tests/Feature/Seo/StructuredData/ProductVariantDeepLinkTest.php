<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\StructuredData;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProductVariantDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.currency' => 'BDT',

            'app.url' => 'https://example.com',
        ]);

        $this->saveSeoSettings();
    }

    public function test_active_variant_query_is_returned_as_selected_variant_without_changing_canonical(): void
    {
        $product =
            $this->variableProduct();

        $variant =
            ProductVariant::factory()
                ->create([
                    'product_id' => $product->id,

                    'sku' => 'CABLE-BLACK',

                    'price' => 85000,

                    'stock_quantity' => 5,

                    'is_active' => true,

                    'is_default' => true,

                    'position' => 0,
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
                        'seo.canonical_url',
                        'https://example.com/products/'
                        .$product->slug,
                    )
                    ->where(
                        'structuredData.@graph.1.@type',
                        'ProductGroup',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.url',
                        'https://example.com/products/'
                        .$product->slug
                        .'?variant='
                        .$variant->id,
                    ),
            );
    }

    public function test_inactive_variant_query_is_not_preselected(): void
    {
        $product =
            $this->variableProduct();

        $variant =
            ProductVariant::factory()
                ->create([
                    'product_id' => $product->id,

                    'sku' => 'INACTIVE-CABLE',

                    'price' => 85000,

                    'is_active' => false,

                    'is_default' => false,
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
                fn (Assert $page) => $page->where(
                    'selectedVariantId',
                    null,
                ),
            );
    }

    public function test_unknown_variant_query_is_not_preselected(): void
    {
        $product =
            $this->variableProduct();

        ProductVariant::factory()
            ->create([
                'product_id' => $product->id,

                'sku' => 'ACTIVE-CABLE',

                'price' => 85000,

                'is_active' => true,

                'is_default' => true,
            ]);

        $this
            ->get(
                '/products/'
                .$product->slug
                .'?variant=999999',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'selectedVariantId',
                        null,
                    )
                    ->where(
                        'seo.canonical_url',
                        'https://example.com/products/'
                        .$product->slug,
                    ),
            );
    }

    public function test_variant_from_another_product_is_not_preselected(): void
    {
        $product =
            $this->variableProduct(
                slug: 'first-cable',
            );

        $otherProduct =
            $this->variableProduct(
                slug: 'second-cable',
            );

        ProductVariant::factory()
            ->create([
                'product_id' => $product->id,

                'sku' => 'FIRST-CABLE-VARIANT',

                'price' => 85000,

                'is_active' => true,

                'is_default' => true,
            ]);

        $otherVariant =
            ProductVariant::factory()
                ->create([
                    'product_id' => $otherProduct->id,

                    'sku' => 'SECOND-CABLE-VARIANT',

                    'price' => 90000,

                    'is_active' => true,

                    'is_default' => true,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug
                .'?variant='
                .$otherVariant->id,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'selectedVariantId',
                    null,
                ),
            );
    }

    private function variableProduct(
        string $slug = 'usb-cable',
    ): Product {
        return Product::factory()
            ->variable()
            ->create([
                'name' => 'USB Cable',

                'slug' => $slug,

                'sku' => null,

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),

                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);
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

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
