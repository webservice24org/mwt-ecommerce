<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProductListingIndexationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings(
            robotsIndex: true,
            robotsFollow: true,
        );
    }

    public function test_base_product_listing_is_indexable(): void
    {
        $this
            ->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Products/Index',
                    )
                    ->where(
                        'robots.index',
                        true,
                    )
                    ->where(
                        'robots.follow',
                        true,
                    ),
            );
    }

    public function test_first_page_product_listing_is_indexable(): void
    {
        $this
            ->get('/products?page=1')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'robots.index',
                        true,
                    )
                    ->where(
                        'robots.follow',
                        true,
                    ),
            );
    }

    public function test_second_page_product_listing_is_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?page=2',
        );
    }

    public function test_search_results_are_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?q=phone',
        );
    }

    public function test_sorted_product_listing_is_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?sort=price_asc',
        );
    }

    public function test_brand_filtered_product_listing_is_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?brand=apple',
        );
    }

    public function test_category_filtered_product_listing_is_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?category=laptops',
        );
    }

    public function test_minimum_price_filtered_product_listing_is_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?min_price=100',
        );
    }

    public function test_maximum_price_filtered_product_listing_is_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?min_price=0&max_price=1000',
        );
    }

    public function test_attribute_filtered_product_listing_is_noindex(): void
    {
        $this->assertListingIsNoindex(
            '/products?attributes[color]=black',
        );
    }

    public function test_global_noindex_wins_on_base_product_listing(): void
    {
        $this->saveSeoSettings(
            robotsIndex: false,
            robotsFollow: true,
        );

        $this
            ->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'robots.index',
                        false,
                    )
                    ->where(
                        'robots.follow',
                        true,
                    ),
            );
    }

    public function test_global_nofollow_is_preserved_on_base_listing(): void
    {
        $this->saveSeoSettings(
            robotsIndex: true,
            robotsFollow: false,
        );

        $this
            ->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'robots.index',
                        true,
                    )
                    ->where(
                        'robots.follow',
                        false,
                    ),
            );
    }

    public function test_global_nofollow_is_preserved_on_noindex_variation(): void
    {
        $this->saveSeoSettings(
            robotsIndex: true,
            robotsFollow: false,
        );

        $this
            ->get('/products?q=phone')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'robots.index',
                        false,
                    )
                    ->where(
                        'robots.follow',
                        false,
                    ),
            );
    }

    private function assertListingIsNoindex(
        string $url,
    ): void {
        $this
            ->get(
                $url,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Products/Index',
                    )
                    ->where(
                        'robots.index',
                        false,
                    )
                    ->where(
                        'robots.follow',
                        true,
                    ),
            );
    }

    private function saveSeoSettings(
        bool $robotsIndex,
        bool $robotsFollow,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default store description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: $robotsIndex,

                defaultRobotsFollow: $robotsFollow,

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
