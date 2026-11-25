<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Sources\CategorySitemapSource;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategorySitemapSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings(
            robotsIndex: true,
        );
    }

    public function test_it_reports_category_sitemap_type(): void
    {
        $source =
            app(
                CategorySitemapSource::class,
            );

        $this->assertSame(
            SitemapType::Categories,
            $source->type(),
        );
    }

    public function test_active_indexable_category_is_included(): void
    {
        $category =
            Category::factory()
                ->create([
                    'slug' => 'laptops',

                    'is_active' => true,
                ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            'https://example.com/category/laptops',
            $urls[0]->url,
        );
    }

    public function test_inactive_category_is_excluded(): void
    {
        Category::factory()
            ->create([
                'is_active' => false,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_noindex_category_is_excluded(): void
    {
        $category =
            Category::factory()
                ->create([
                    'is_active' => true,
                ]);

        $category
            ->seoMeta()
            ->create([
                'robots_index' => false,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_category_canonical_override_is_used(): void
    {
        $category =
            Category::factory()
                ->create([
                    'is_active' => true,
                ]);

        $category
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://canonical.example.com/laptops',
            ]);

        $urls =
            $this->urls();

        $this->assertSame(
            'https://canonical.example.com/laptops',
            $urls[0]->url,
        );
    }

    /**
     * @return list<SitemapUrlData>
     */
    private function urls(): array
    {
        return iterator_to_array(
            app(
                CategorySitemapSource::class,
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
