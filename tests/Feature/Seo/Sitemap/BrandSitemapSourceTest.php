<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Sources\BrandSitemapSource;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandSitemapSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings(
            robotsIndex: true,
        );
    }

    public function test_it_reports_brand_sitemap_type(): void
    {
        $source =
            app(
                BrandSitemapSource::class,
            );

        $this->assertSame(
            SitemapType::Brands,
            $source->type(),
        );
    }

    public function test_active_indexable_brand_is_included(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'apple',

                    'is_active' => true,
                ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            "https://example.com/brand/{$brand->slug}",
            $urls[0]->url,
        );
    }

    public function test_inactive_brand_is_excluded(): void
    {
        Brand::factory()
            ->create([
                'is_active' => false,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_noindex_brand_is_excluded(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'is_active' => true,
                ]);

        $brand
            ->seoMeta()
            ->create([
                'robots_index' => false,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_brand_inherits_global_noindex(): void
    {
        $this->saveSeoSettings(
            robotsIndex: false,
        );

        Brand::factory()
            ->create([
                'is_active' => true,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_explicit_index_override_can_include_brand(): void
    {
        $this->saveSeoSettings(
            robotsIndex: false,
        );

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

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            'https://example.com/brand/explicit-index-brand',
            $urls[0]->url,
        );
    }

    public function test_brand_canonical_override_is_used(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'is_active' => true,
                ]);

        $brand
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://canonical.example.com/apple',
            ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            'https://canonical.example.com/apple',
            $urls[0]->url,
        );
    }

    public function test_last_modified_uses_brand_updated_at(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'is_active' => true,

                    'updated_at' => '2026-11-17 15:30:00',
                ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            $brand
                ->updated_at
                ?->toImmutable()
                ->getTimestamp(),

            $urls[0]
                ->lastModified
                ?->getTimestamp(),
        );
    }

    /**
     * @return list<SitemapUrlData>
     */
    private function urls(): array
    {
        return iterator_to_array(
            app(
                BrandSitemapSource::class,
            )->urls(),

            false,
        );
    }

    private function saveSeoSettings(
        bool $robotsIndex,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: $robotsIndex,

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
