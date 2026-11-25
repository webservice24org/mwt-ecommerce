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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SitemapSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();

        $this->saveSeoSettings(
            canonicalHost: 'https://example.com',

            defaultRobotsIndex: true,

            enableSitemap: true,
        );
    }

    public function test_invalid_sitemap_type_returns_not_found(): void
    {
        $this
            ->get(
                '/unknown-sitemap-1.xml',
            )
            ->assertNotFound();
    }

    public function test_zero_part_number_returns_not_found(): void
    {
        $this
            ->get(
                '/products-sitemap-0.xml',
            )
            ->assertNotFound();
    }

    public function test_negative_part_number_returns_not_found(): void
    {
        $this
            ->get(
                '/products-sitemap--1.xml',
            )
            ->assertNotFound();
    }

    public function test_non_numeric_part_returns_not_found(): void
    {
        $this
            ->get(
                '/products-sitemap-one.xml',
            )
            ->assertNotFound();
    }

    public function test_unreasonably_large_missing_part_returns_not_found(): void
    {
        Product::factory()
            ->create([
                'slug' => 'test-product',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),
            ]);

        $this
            ->get(
                '/products-sitemap-999999999.xml',
            )
            ->assertNotFound();
    }

    public function test_path_traversal_like_sitemap_request_does_not_resolve(): void
    {
        $this
            ->get(
                '/products-sitemap-../1.xml',
            )
            ->assertNotFound();
    }

    public function test_sitemap_index_has_nosniff_header(): void
    {
        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff',
            );
    }

    public function test_sitemap_part_has_nosniff_header(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff',
            );
    }

    public function test_sitemap_index_uses_xml_content_type(): void
    {
        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/xml; charset=UTF-8',
            );
    }

    public function test_sitemap_part_uses_xml_content_type(): void
    {
        Category::factory()
            ->create([
                'slug' => 'chargers',

                'is_active' => true,
            ]);

        $this
            ->get(
                '/categories-sitemap-1.xml',
            )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/xml; charset=UTF-8',
            );
    }

    public function test_draft_product_is_never_exposed(): void
    {
        Product::factory()
            ->create([
                'slug' => 'secret-draft-product',

                'status' => ProductStatus::Draft,
            ]);

        $response =
            $this->get(
                '/products-sitemap-1.xml',
            );

        if (
            $response->status() ===
            404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'secret-draft-product',
                false,
            );
    }

    public function test_future_product_is_never_exposed_early(): void
    {
        Product::factory()
            ->create([
                'slug' => 'future-product',

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->addHour(),
            ]);

        $response =
            $this->get(
                '/products-sitemap-1.xml',
            );

        if (
            $response->status() ===
            404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'future-product',
                false,
            );
    }

    public function test_draft_page_is_never_exposed(): void
    {
        Page::factory()
            ->create([
                'title' => 'Private Draft',

                'slug' => 'private-draft',

                'status' => PageStatus::Draft,
            ]);

        $response =
            $this->get(
                '/pages-sitemap-1.xml',
            );

        if (
            $response->status() ===
            404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'private-draft',
                false,
            );
    }

    public function test_future_page_is_not_exposed_early(): void
    {
        Page::factory()
            ->create([
                'title' => 'Future Page',

                'slug' => 'future-page',

                'status' => PageStatus::Published,

                'published_at' => now()
                    ->addHour(),
            ]);

        $response =
            $this->get(
                '/pages-sitemap-1.xml',
            );

        if (
            $response->status() ===
            404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'future-page',
                false,
            );
    }

    public function test_inactive_category_is_never_exposed(): void
    {
        Category::factory()
            ->create([
                'slug' => 'inactive-category',

                'is_active' => false,
            ]);

        $response =
            $this->get(
                '/categories-sitemap-1.xml',
            );

        if (
            $response->status() ===
            404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'inactive-category',
                false,
            );
    }

    public function test_inactive_brand_is_never_exposed(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'inactive-brand',

                'is_active' => false,
            ]);

        $response =
            $this->get(
                '/brands-sitemap-1.xml',
            );

        if (
            $response->status() ===
            404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'inactive-brand',
                false,
            );
    }

    public function test_explicit_noindex_product_is_never_exposed(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'noindex-product',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $product
            ->seoMeta()
            ->create([
                'robots_index' => false,
            ]);

        $response =
            $this->get(
                '/products-sitemap-1.xml',
            );

        if (
            $response->status() ===
            404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'noindex-product',
                false,
            );
    }

    public function test_unsafe_canonical_override_is_not_exposed(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'safe-product',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'javascript:alert(1)',

                'robots_index' => true,
            ]);

        $response =
            $this
                ->get(
                    '/products-sitemap-1.xml',
                )
                ->assertOk();

        $response
            ->assertDontSee(
                'javascript:',
                false,
            )
            ->assertSee(
                'https://example.com/products/safe-product',
                false,
            );
    }

    public function test_data_scheme_canonical_override_is_not_exposed(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'secure-product',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'data:text/html,test',

                'robots_index' => true,
            ]);

        $response =
            $this
                ->get(
                    '/products-sitemap-1.xml',
                )
                ->assertOk();

        $response
            ->assertDontSee(
                'data:',
                false,
            )
            ->assertSee(
                'https://example.com/products/secure-product',
                false,
            );
    }

    public function test_sitemap_is_unavailable_when_globally_disabled(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $this->saveSeoSettings(
            canonicalHost: 'https://example.com',

            defaultRobotsIndex: true,

            enableSitemap: false,
        );

        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertNotFound();

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertNotFound();
    }

    private function saveSeoSettings(
        string $canonicalHost,
        bool $defaultRobotsIndex,
        bool $enableSitemap,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default description.',

                canonicalHost: $canonicalHost,

                defaultRobotsIndex: $defaultRobotsIndex,

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

                enableSitemap: $enableSitemap,
            ),
        );
    }
}
