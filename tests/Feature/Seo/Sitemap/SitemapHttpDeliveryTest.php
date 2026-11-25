<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SitemapHttpDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings(
            sitemapEnabled: true,
        );
    }

    public function test_sitemap_index_is_publicly_delivered_as_xml(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $response =
            $this->get(
                '/sitemap.xml',
            );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/xml; charset=UTF-8',
            );

        $response->assertSee(
            '<?xml version="1.0" encoding="UTF-8"?>',
            false,
        );

        $response->assertSee(
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            false,
        );

        $response->assertSee(
            'https://example.com/brands-sitemap-1.xml',
            false,
        );
    }

    public function test_brand_sitemap_part_is_publicly_delivered(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $response =
            $this->get(
                '/brands-sitemap-1.xml',
            );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/xml; charset=UTF-8',
            );

        $response->assertSee(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            false,
        );

        $response->assertSee(
            '<loc>https://example.com/brand/mcdodo</loc>',
            false,
        );
    }

    public function test_missing_part_returns_not_found(): void
    {
        $response =
            $this->get(
                '/brands-sitemap-999.xml',
            );

        $response
            ->assertNotFound();
    }

    public function test_invalid_sitemap_part_number_does_not_match_route(): void
    {
        $this
            ->get(
                '/brands-sitemap-0.xml',
            )
            ->assertNotFound();
    }

    public function test_sitemap_endpoints_are_not_available_when_disabled(): void
    {
        $this->saveSeoSettings(
            sitemapEnabled: false,
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
        bool $sitemapEnabled,
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

                enableSitemap: $sitemapEnabled,
            ),
        );
    }

    public function test_index_lists_every_non_empty_sitemap_source(): void
    {
        Brand::factory()
            ->create([
                'slug' => 'mcdodo',

                'is_active' => true,
            ]);

        $response =
            $this->get(
                '/sitemap.xml',
            );

        $response
            ->assertOk();

        /*
         * Brand exists, so Brand sitemap must definitely appear.
         *
         * Other sitemap types should only appear when their
         * sources actually contain URLs.
         */
        $response->assertSee(
            'https://example.com/brands-sitemap-1.xml',
            false,
        );
    }

    public function test_index_filename_resolves_to_real_public_part(): void
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
            ->assertOk()
            ->assertSee(
                'https://example.com/brands-sitemap-1.xml',
                false,
            );

        $this
            ->get(
                '/brands-sitemap-1.xml',
            )
            ->assertOk();
    }
}
