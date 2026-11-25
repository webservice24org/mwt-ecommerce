<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class BrandStorefrontSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_active_brand_page_receives_authoritative_seo(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Apple',

                    'slug' => 'apple',

                    'description' => 'Shop Apple products.',

                    'is_active' => true,

                    'meta_title' => 'Apple Products',

                    'meta_description' => 'Browse Apple products.',
                ]);

        $brand
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/brands/apple-custom',

                'robots_index' => true,

                'robots_follow' => true,

                'og_title' => 'Apple OG',

                'og_description' => 'Apple OG description.',

                'og_image' => 'https://example.com/apple-og.jpg',
            ]);

        $this
            ->get(
                route(
                    'brands.show',
                    [
                        'slug' => $brand->slug,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->component(
                        'Frontend/Brands/Show',
                    )
                    ->where(
                        'brand.name',
                        'Apple',
                    )
                    ->where(
                        'brand.slug',
                        'apple',
                    )
                    ->where(
                        'seo.title',
                        'Apple Products | MWT Store',
                    )
                    ->where(
                        'seo.description',
                        'Browse Apple products.',
                    )
                    ->where(
                        'seo.canonical_override',
                        'https://example.com/brands/apple-custom',
                    )
                    ->where(
                        'seo.canonical_url',
                        'https://example.com/brands/apple-custom',
                    )
                    ->where(
                        'seo.robots_index',
                        true,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    )
                    ->where(
                        'seo.og_title',
                        'Apple OG',
                    )
                    ->has(
                        'products',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'filterOptions',
                    ),
            );
    }

    public function test_inactive_brand_returns_not_found(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'inactive-brand',

                    'is_active' => false,
                ]);

        $this
            ->get(
                route(
                    'brands.show',
                    [
                        'slug' => $brand->slug,
                    ],
                ),
            )
            ->assertNotFound();
    }

    public function test_base_brand_preserves_resolved_index_policy(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'indexable-brand',

                    'is_active' => true,
                ]);

        $brand
            ->seoMeta()
            ->create([
                'robots_index' => true,

                'robots_follow' => true,
            ]);

        $this
            ->get(
                route(
                    'brands.show',
                    [
                        'slug' => $brand->slug,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->where(
                        'seo.robots_index',
                        true,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    ),
            );
    }

    public function test_filtered_brand_is_forced_to_noindex(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'filtered-brand',

                    'is_active' => true,
                ]);

        $brand
            ->seoMeta()
            ->create([
                'robots_index' => true,

                'robots_follow' => true,
            ]);

        $this
            ->get(
                route(
                    'brands.show',
                    [
                        'slug' => $brand->slug,

                        'category' => 'laptops',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->where(
                        'seo.robots_index',
                        false,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    ),
            );
    }

    public function test_route_policy_never_promotes_brand_noindex(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'slug' => 'noindex-brand',

                    'is_active' => true,
                ]);

        $brand
            ->seoMeta()
            ->create([
                'robots_index' => false,

                'robots_follow' => true,
            ]);

        $this
            ->get(
                route(
                    'brands.show',
                    [
                        'slug' => $brand->slug,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->where(
                        'seo.robots_index',
                        false,
                    ),
            );
    }
}
