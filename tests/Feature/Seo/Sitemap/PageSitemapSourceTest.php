<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Services\SitemapRegistry;
use App\Domain\Seo\Sitemap\Sources\PageSitemapSource;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageSitemapSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings(
            robotsIndex: true,
        );
    }

    public function test_it_reports_page_sitemap_type(): void
    {
        $source =
            app(
                PageSitemapSource::class,
            );

        $this->assertSame(
            SitemapType::Pages,
            $source->type(),
        );
    }

    public function test_published_indexable_page_is_included(): void
    {
        Page::factory()
            ->create([
                'slug' => 'about-us',

                'status' => PageStatus::Published,

                'published_at' => now()
                    ->subDay(),
            ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            'https://example.com/pages/about-us',
            $urls[0]->url,
        );
    }

    public function test_draft_page_is_excluded(): void
    {
        Page::factory()
            ->create([
                'status' => PageStatus::Draft,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_future_published_page_is_excluded(): void
    {
        Page::factory()
            ->create([
                'status' => PageStatus::Published,

                'published_at' => now()
                    ->addDay(),
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_noindex_page_is_excluded(): void
    {
        $page =
            Page::factory()
                ->create([
                    'status' => PageStatus::Published,

                    'published_at' => now()
                        ->subDay(),
                ]);

        $page
            ->seoMeta()
            ->create([
                'robots_index' => false,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_page_canonical_override_is_used(): void
    {
        $page =
            Page::factory()
                ->create([
                    'status' => PageStatus::Published,

                    'published_at' => now()
                        ->subDay(),
                ]);

        $page
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://canonical.example.com/about',
            ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            'https://canonical.example.com/about',
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
                PageSitemapSource::class,
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

    public function test_storefront_sitemap_sources_are_registered(): void
    {
        $registry =
            app(
                SitemapRegistry::class,
            );

        $this->assertTrue(
            $registry->has(
                SitemapType::Products,
            ),
        );

        $this->assertTrue(
            $registry->has(
                SitemapType::Categories,
            ),
        );

        $this->assertTrue(
            $registry->has(
                SitemapType::Pages,
            ),
        );

        $this->assertTrue(
            $registry->has(
                SitemapType::Brands,
            ),
        );
    }
}
