<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontSocialDefaultImageShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_receives_global_default_open_graph_image(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default store description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: '/storage/seo/default-social-card.jpg',

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

        $product =
            Product::factory()
                ->create([
                    'name' => 'G2 Shared Settings Product',

                    'slug' => 'g2-shared-settings-product',

                    'price' => 125000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'storefrontSocialSeo.default_og_image',
                    '/storage/seo/default-social-card.jpg',
                ),
            );
    }

    public function test_null_default_image_is_shared_as_null_without_inventing_a_value(): void
    {
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

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );

        $product =
            Product::factory()
                ->create([
                    'name' => 'G2 Null Image Product',

                    'slug' => 'g2-null-image-product',

                    'price' => 125000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'storefrontSocialSeo.default_og_image',
                    null,
                ),
            );
    }
}
