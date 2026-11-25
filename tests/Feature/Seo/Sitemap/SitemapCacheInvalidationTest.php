<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Services\SitemapCache;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SitemapCacheInvalidationTest extends TestCase
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

    public function test_product_update_invalidates_cached_product_sitemap(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Old Product',

                    'slug' => 'old-product',

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
                'https://example.com/products/old-product',
                false,
            );

        $product->update([
            'slug' => 'new-product',
        ]);

        $this
            ->get(
                '/products-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/products/new-product',
                false,
            )
            ->assertDontSee(
                'https://example.com/products/old-product',
                false,
            );
    }

    public function test_category_update_invalidates_cached_category_sitemap(): void
    {
        $category =
            Category::factory()
                ->create([
                    'name' => 'Old Category',

                    'slug' => 'old-category',

                    'is_active' => true,
                ]);

        $this
            ->get(
                '/categories-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/category/old-category',
                false,
            );

        $category->update([
            'slug' => 'new-category',
        ]);

        $this
            ->get(
                '/categories-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/category/new-category',
                false,
            )
            ->assertDontSee(
                'https://example.com/category/old-category',
                false,
            );
    }

    public function test_brand_update_invalidates_cached_brand_sitemap(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Old Brand',

                    'slug' => 'old-brand',

                    'is_active' => true,
                ]);

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/brand/old-brand',
                false,
            );

        $brand->update([
            'slug' => 'new-brand',
        ]);

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/brand/new-brand',
                false,
            )
            ->assertDontSee(
                'https://example.com/brand/old-brand',
                false,
            );
    }

    public function test_page_update_invalidates_cached_page_sitemap(): void
    {
        $page =
            Page::factory()
                ->create([
                    'title' => 'Old Page',

                    'slug' => 'old-page',

                    'status' => PageStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $this
            ->get(
                '/pages-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/pages/old-page',
                false,
            );

        $page->update([
            'slug' => 'new-page',
        ]);

        $this
            ->get(
                '/pages-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/pages/new-page',
                false,
            )
            ->assertDontSee(
                'https://example.com/pages/old-page',
                false,
            );
    }

    public function test_canonical_override_change_invalidates_cached_product_sitemap(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'charger',

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
                'https://example.com/products/charger',
                false,
            );

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/custom/charger',

                'robots_index' => true,
            ]);

        $this
            ->get(
                '/products-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/custom/charger',
                false,
            )
            ->assertDontSee(
                'https://example.com/products/charger',
                false,
            );
    }

    public function test_noindex_change_removes_product_from_cached_sitemap(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'hidden-product',

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
                'https://example.com/products/hidden-product',
                false,
            );

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
            $response->status()
            === 404
        ) {
            $response
                ->assertNotFound();

            return;
        }

        $response
            ->assertOk()
            ->assertDontSee(
                'https://example.com/products/hidden-product',
                false,
            );
    }

    public function test_restoring_indexability_recreates_product_sitemap(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'restored-product',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $seoMeta =
            $product
                ->seoMeta()
                ->create([
                    'robots_index' => false,
                ]);

        $this
            ->get(
                '/products-sitemap-1.xml',
            )
            ->assertNotFound();

        $seoMeta->update([
            'robots_index' => true,
        ]);

        $this
            ->get(
                '/products-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/products/restored-product',
                false,
            );
    }

    public function test_product_archive_invalidates_cached_sitemap(): void
    {
        $product =
            Product::factory()
                ->create([
                    'slug' => 'published-product',

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
                'https://example.com/products/published-product',
                false,
            );

        $product->update([
            'status' => ProductStatus::Archived,
        ]);

        $this
            ->get(
                '/products-sitemap-1.xml',
            )
            ->assertNotFound();
    }

    public function test_inactive_category_is_removed_from_cached_sitemap(): void
    {
        $category =
            Category::factory()
                ->create([
                    'slug' => 'temporary-category',

                    'is_active' => true,
                ]);

        $this
            ->get(
                '/categories-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/category/temporary-category',
                false,
            );

        $category->update([
            'is_active' => false,
        ]);

        $this
            ->get(
                '/categories-sitemap-1.xml',
            )
            ->assertNotFound();
    }

    public function test_inactive_brand_is_removed_from_cached_sitemap(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'temporary-brand',

                    'is_active' => true,
                ]);

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/brand/temporary-brand',
                false,
            );

        $brand->update([
            'is_active' => false,
        ]);

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertNotFound();
    }

    public function test_unpublished_page_is_removed_from_cached_sitemap(): void
    {
        $page =
            Page::factory()
                ->create([
                    'slug' => 'temporary-page',

                    'status' => PageStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $this
            ->get(
                '/pages-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/pages/temporary-page',
                false,
            );

        $page->update([
            'status' => PageStatus::Draft,
        ]);

        $this
            ->get(
                '/pages-sitemap-1.xml',
            )
            ->assertNotFound();
    }

    public function test_entity_creation_invalidates_cached_sitemap_index(): void
    {
        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertOk()
            ->assertDontSee(
                'brands-sitemap-1.xml',
                false,
            );

        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://example.com/brands-sitemap-1.xml',
                false,
            );
    }

    public function test_entity_deletion_invalidates_cached_sitemap_index(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'mcdodo',

                    'is_active' => true,
                ]);

        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertOk()
            ->assertSee(
                'brands-sitemap-1.xml',
                false,
            );

        $brand->delete();

        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertOk()
            ->assertDontSee(
                'brands-sitemap-1.xml',
                false,
            );
    }

    public function test_global_canonical_host_change_invalidates_cached_sitemap(): void
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
            ->assertSee(
                'https://example.com/brand/mcdodo',
                false,
            );

        $this->saveSeoSettings(
            canonicalHost: 'https://store.example.org',

            defaultRobotsIndex: true,

            enableSitemap: true,
        );

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                'https://store.example.org/brand/mcdodo',
                false,
            )
            ->assertDontSee(
                'https://example.com/brand/mcdodo',
                false,
            );
    }

    public function test_global_default_noindex_invalidates_cached_sitemap(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'inherited-brand',

                'is_active' => true,
            ]);

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                '/brand/inherited-brand',
                false,
            );

        $this->saveSeoSettings(
            canonicalHost: 'https://example.com',

            defaultRobotsIndex: false,

            enableSitemap: true,
        );

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertNotFound();
    }

    public function test_explicit_index_override_survives_global_default_noindex_after_invalidation(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'explicit-index-brand',

                    'is_active' => true,
                ]);

        $brand
            ->seoMeta()
            ->create([
                'robots_index' => true,
            ]);

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                '/brand/explicit-index-brand',
                false,
            );

        $this->saveSeoSettings(
            canonicalHost: 'https://example.com',

            defaultRobotsIndex: false,

            enableSitemap: true,
        );

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk()
            ->assertSee(
                '/brand/explicit-index-brand',
                false,
            );
    }

    public function test_sitemap_disable_takes_effect_after_cached_xml_exists(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $this
            ->get(
                '/sitemap.xml',
            )
            ->assertOk();

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

    public function test_sitemap_invalidation_does_not_flush_unrelated_cache(): void
    {
        Cache::put(
            'unrelated:key',
            'keep-me',
            3600,
        );

        app(
            SitemapCache::class,
        )->invalidate();

        self::assertSame(
            'keep-me',
            Cache::get(
                'unrelated:key',
            ),
        );
    }

    public function test_sitemap_version_advances_after_relevant_entity_update(): void
    {
        $cache =
            app(
                SitemapCache::class,
            );

        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'first-brand',

                    'is_active' => true,
                ]);

        $versionAfterCreate =
            $cache->version();

        $brand->update([
            'name' => 'Updated Brand Name',
        ]);

        self::assertGreaterThan(
            $versionAfterCreate,
            $cache->version(),
        );
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
