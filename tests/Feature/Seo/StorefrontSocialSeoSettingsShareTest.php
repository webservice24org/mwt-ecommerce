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

final class StorefrontSocialSeoSettingsShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_receives_authoritative_social_policy(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Social Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: null,

                twitterCard: 'summary',

                organizationName: 'MWT Social Store',

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: false,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );

        $product =
            Product::factory()
                ->create([
                    'name' => 'Social Metadata Product',

                    'slug' => 'social-metadata-product',

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
                fn (Assert $page) => $page
                    ->where(
                        'storefrontSocialSeo.site_name',
                        'MWT Social Store',
                    )
                    ->where(
                        'storefrontSocialSeo.enable_open_graph',
                        false,
                    )
                    ->where(
                        'storefrontSocialSeo.enable_twitter_cards',
                        true,
                    )
                    ->where(
                        'storefrontSocialSeo.twitter_card',
                        'summary',
                    ),
            );
    }
}
