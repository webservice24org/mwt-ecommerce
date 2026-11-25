<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SitemapEndToEndRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();

        $this->saveSeoSettings();
    }

    public function test_complete_sitemap_index_and_parts_are_consistent(): void
    {
        Product::factory()
            ->create([
                'slug' => 'usb-charger',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        Category::factory()
            ->create([
                'slug' => 'chargers',

                'is_active' => true,
            ]);

        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        Page::factory()
            ->create([
                'title' => 'About Us',

                'slug' => 'about-us',

                'status' => PageStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        $index =
            $this
                ->get(
                    '/sitemap.xml',
                )
                ->assertOk();

        $index
            ->assertSee(
                'https://example.com/products-sitemap-1.xml',
                false,
            )
            ->assertSee(
                'https://example.com/categories-sitemap-1.xml',
                false,
            )
            ->assertSee(
                'https://example.com/brands-sitemap-1.xml',
                false,
            )
            ->assertSee(
                'https://example.com/pages-sitemap-1.xml',
                false,
            );

        $this
            ->get(
                '/products-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/products/usb-charger',
                false,
            );

        $this
            ->get(
                '/categories-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/category/chargers',
                false,
            );

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/brand/mcdodo',
                false,
            );

        $this
            ->get(
                '/pages-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/pages/about-us',
                false,
            );
    }

    public function test_sitemap_index_is_well_formed_xml(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $response =
            $this
                ->get(
                    '/sitemap.xml',
                )
                ->assertOk();

        self::assertTrue(
            $this->isValidXml(
                $response->getContent(),
            ),
        );
    }

    public function test_each_sitemap_part_is_well_formed_xml(): void
    {
        Product::factory()
            ->create([
                'slug' => 'product-one',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        Category::factory()
            ->create([
                'slug' => 'category-one',

                'is_active' => true,
            ]);

        Brand::factory()
            ->create([
                'slug' => 'brand-one',

                'is_active' => true,
            ]);

        Page::factory()
            ->create([
                'title' => 'Page One',

                'slug' => 'page-one',

                'status' => PageStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        foreach (
            [
                '/products-sitemap-1.xml',
                '/categories-sitemap-1.xml',
                '/brands-sitemap-1.xml',
                '/pages-sitemap-1.xml',
            ] as $path
        ) {
            $response =
                $this
                    ->get(
                        $path,
                    )
                    ->assertOk();

            self::assertTrue(
                $this->isValidXml(
                    $response->getContent(),
                ),
                "Invalid XML returned by {$path}",
            );
        }
    }

    public function test_index_does_not_reference_empty_sources(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $response =
            $this
                ->get(
                    '/sitemap.xml',
                )
                ->assertOk();

        $response
            ->assertSee(
                'brands-sitemap-1.xml',
                false,
            )
            ->assertDontSee(
                'products-sitemap-1.xml',
                false,
            )
            ->assertDontSee(
                'categories-sitemap-1.xml',
                false,
            )
            ->assertDontSee(
                'pages-sitemap-1.xml',
                false,
            );
    }

    public function test_no_duplicate_locations_exist_within_product_sitemap(): void
    {
        Product::factory()
            ->create([
                'slug' => 'one',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        Product::factory()
            ->create([
                'slug' => 'two',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        $response =
            $this
                ->get(
                    '/products-sitemap-1.xml',
                )
                ->assertOk();

        $locations =
            $this->locations(
                $response->getContent(),
            );

        self::assertSame(
            $locations,
            array_values(
                array_unique(
                    $locations,
                ),
            ),
        );
    }

    public function test_all_locations_are_absolute_http_or_https_urls(): void
    {
        Product::factory()
            ->create([
                'slug' => 'secure-product',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        $response =
            $this
                ->get(
                    '/products-sitemap-1.xml',
                )
                ->assertOk();

        foreach (
            $this->locations(
                $response->getContent(),
            ) as $location
        ) {
            self::assertTrue(
                str_starts_with(
                    $location,
                    'https://',
                )
                || str_starts_with(
                    $location,
                    'http://',
                ),
            );
        }
    }

    public function test_sitemap_locations_do_not_contain_fragments(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'fragment-product',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/custom/product#section',

                'robots_index' => true,
            ]);

        $response =
            $this
                ->get(
                    '/products-sitemap-1.xml',
                )
                ->assertOk();

        foreach (
            $this->locations(
                $response->getContent(),
            ) as $location
        ) {
            self::assertStringNotContainsString(
                '#',
                $location,
            );
        }
    }

    public function test_lastmod_exists_for_entity_urls(): void
    {
        Product::factory()
            ->create([
                'slug' => 'lastmod-product',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        $this
            ->get(
                '/products-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                '<lastmod>',
                false,
            );
    }

    /**
     * @return list<string>
     */
    private function locations(
        string $xml,
    ): array {
        $document =
            new DOMDocument;

        $loaded =
            $document
                ->loadXML(
                    $xml,
                    LIBXML_NONET,
                );

        self::assertTrue(
            $loaded,
        );

        $locations = [];

        foreach (
            $document
                ->getElementsByTagName(
                    'loc',
                ) as $node
        ) {
            $locations[] =
                trim(
                    $node
                        ->textContent,
                );
        }

        return $locations;
    }

    private function isValidXml(
        string $xml,
    ): bool {
        $previous =
            libxml_use_internal_errors(
                true,
            );

        try {
            $document =
                new DOMDocument;

            return $document
                ->loadXML(
                    $xml,
                    LIBXML_NONET,
                );
        } finally {
            libxml_clear_errors();

            libxml_use_internal_errors(
                $previous,
            );
        }
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

                defaultMetaDescription: 'Default description.',

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
