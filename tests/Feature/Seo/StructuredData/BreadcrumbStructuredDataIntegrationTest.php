<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\StructuredData;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class BreadcrumbStructuredDataIntegrationTest extends TestCase
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

    public function test_product_page_receives_server_authoritative_breadcrumb_schema(): void
    {
        $category =
            Category::factory()
                ->create([
                    'name' => 'Chargers',

                    'slug' => 'chargers',

                    'is_active' => true,
                ]);

        $product =
            Product::factory()
                ->create([
                    'name' => 'USB Charger',

                    'slug' => 'usb-charger',

                    'type' => ProductType::Simple,

                    'sku' => 'CHG-001',

                    'price' => 485000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $product
            ->categories()
            ->attach(
                $category->id,
            );

        $this
            ->get(
                '/products/usb-charger',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                        /*
                         * F.4:
                         *
                         * Product pages now receive one merged
                         * page-level structured-data document.
                         */
                    ->where(
                        'structuredData.@context',
                        'https://schema.org',
                    )

                        /*
                         * BreadcrumbList remains the first node.
                         */
                    ->where(
                        'structuredData.@graph.0.@type',
                        'BreadcrumbList',
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.0.position',
                        1,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.0.name',
                        'Home',
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.0.item',
                        'https://example.com',
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.1.position',
                        2,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.1.name',
                        'Shop',
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.1.item',
                        'https://example.com/products',
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.2.position',
                        3,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.2.name',
                        'Chargers',
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.2.item',
                        'https://example.com/category/chargers',
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.3.position',
                        4,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.3.name',
                        'USB Charger',
                    )

                        /*
                         * The current-page breadcrumb deliberately
                         * has no item URL.
                         */
                    ->missing(
                        'structuredData.@graph.0.itemListElement.3.item',
                    )

                        /*
                         * F.4 Product node is merged into the same
                         * document after the BreadcrumbList.
                         */
                    ->where(
                        'structuredData.@graph.1.@type',
                        'Product',
                    )
                    ->where(
                        'structuredData.@graph.1.@id',
                        'https://example.com/products/usb-charger#product',
                    )
                    ->where(
                        'structuredData.@graph.1.url',
                        'https://example.com/products/usb-charger',
                    )
                    ->where(
                        'structuredData.@graph.1.name',
                        'USB Charger',
                    )
                    ->where(
                        'structuredData.@graph.1.sku',
                        'CHG-001',
                    )

                        /*
                         * F.4 Offer foundation.
                         */
                    ->where(
                        'structuredData.@graph.1.offers.@type',
                        'Offer',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.@id',
                        'https://example.com/products/usb-charger#offer',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.price',
                        '4850.00',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.priceCurrency',
                        'BDT',
                    ),
            );
    }

    public function test_product_structured_data_is_disabled_globally(): void
    {
        $this->saveSeoSettings(
            enabled: false,
        );

        Product::factory()
            ->create([
                'name' => 'USB Charger',

                'slug' => 'usb-charger',

                'type' => ProductType::Simple,

                'sku' => 'CHG-001',

                'price' => 485000,

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        $this
            ->get(
                '/products/usb-charger',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'structuredData',
                    null,
                ),
            );
    }

    public function test_page_with_breadcrumbs_disabled_receives_no_breadcrumb_schema(): void
    {
        $page =
            Page::factory()
                ->create([
                    'title' => 'About Us',

                    'slug' => 'about-us',

                    'status' => PageStatus::Published,

                    'published_at' => now()
                        ->subMinute(),

                    'show_breadcrumbs' => false,
                ]);

        $this
            ->get(
                '/pages/'.$page->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $response) => $response->where(
                    'breadcrumbStructuredData',
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
