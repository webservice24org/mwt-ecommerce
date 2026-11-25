<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class OpenGraphSocialImageSeoClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_product_image_drives_authoritative_social_image_payload_and_keeps_structured_data(): void
    {
        $this->saveSettings(
            defaultOgImage: '/storage/seo/global-default.jpg',
        );

        $product =
            Product::factory()
                ->create([
                    'name' => 'Closure Headphones',

                    'slug' => 'closure-headphones',

                    'price' => 125000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/secondary-headphones.jpg',

                'alt_text' => 'Headphones side detail',

                'width' => 1000,

                'height' => 1000,

                'position' => 0,

                'is_primary' => false,
            ]);

        $primary =
            ProductImage::factory()
                ->create([
                    'product_id' => $product->id,

                    'path' => 'products/closure-headphones.jpg',

                    'alt_text' => 'Black over-ear headphones on a white background',

                    'width' => 1600,

                    'height' => 1600,

                    /*
                     * Deliberately larger than the secondary
                     * image position to prove primary-first
                     * storefront ordering.
                     */
                    'position' => 10,

                    'is_primary' => true,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Products/Show',
                    )
                    ->where(
                        'seo.canonical_url',
                        'https://example.com/products/closure-headphones',
                    )
                    ->where(
                        'product.images.0.id',
                        $primary->id,
                    )
                    ->where(
                        'product.images.0.alt',
                        'Black over-ear headphones on a white background',
                    )
                    ->where(
                        'product.images.0.width',
                        1600,
                    )
                    ->where(
                        'product.images.0.height',
                        1600,
                    )
                    ->where(
                        'seo.og_image',
                        fn (mixed $value): bool => is_string(
                            $value,
                        )
                            && str_contains(
                                $value,
                                'closure-headphones.jpg',
                            ),
                    )
                    ->where(
                        'seo.twitter_image',
                        fn (mixed $value): bool => is_string(
                            $value,
                        )
                            && str_contains(
                                $value,
                                'closure-headphones.jpg',
                            ),
                    )
                    ->where(
                        'storefrontSocialSeo.enable_open_graph',
                        true,
                    )
                    ->where(
                        'storefrontSocialSeo.enable_twitter_cards',
                        true,
                    )
                    ->where(
                        'storefrontSocialSeo.twitter_card',
                        'summary_large_image',
                    )
                    ->where(
                        'storefrontSocialSeo.default_og_image',
                        '/storage/seo/global-default.jpg',
                    )
                    ->has(
                        'structuredData',
                    ),
            );
    }

    public function test_explicit_social_image_overrides_win_without_changing_the_product_primary_image(): void
    {
        $this->saveSettings(
            defaultOgImage: 'https://example.com/seo/global-default.jpg',
        );

        $product =
            Product::factory()
                ->create([
                    'name' => 'Override Product',

                    'slug' => 'override-product',

                    'price' => 99000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $primary =
            ProductImage::factory()
                ->create([
                    'product_id' => $product->id,

                    'path' => 'products/override-primary.jpg',

                    'alt_text' => 'Real primary product photograph',

                    'width' => 1400,

                    'height' => 1400,

                    'position' => 0,

                    'is_primary' => true,
                ]);

        $product
            ->seoMeta()
            ->create([
                'og_image' => 'https://example.com/social/custom-og.jpg',

                'twitter_image' => 'https://example.com/social/custom-x.jpg',
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
                        'product.images.0.id',
                        $primary->id,
                    )
                    ->where(
                        'product.images.0.alt',
                        'Real primary product photograph',
                    )
                    ->where(
                        'seo.og_image',
                        'https://example.com/social/custom-og.jpg',
                    )
                    ->where(
                        'seo.twitter_image',
                        'https://example.com/social/custom-x.jpg',
                    )
                    ->has(
                        'structuredData',
                    ),
            );
    }

    public function test_product_without_image_uses_global_default_social_image(): void
    {
        $this->saveSettings(
            defaultOgImage: 'https://example.com/seo/global-default.jpg',
        );

        $product =
            Product::factory()
                ->create([
                    'name' => 'No Image Product',

                    'slug' => 'no-image-product',

                    'price' => 75000,

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
                        'seo.og_image',
                        'https://example.com/seo/global-default.jpg',
                    )
                    ->where(
                        'seo.twitter_image',
                        'https://example.com/seo/global-default.jpg',
                    )
                    ->where(
                        'storefrontSocialSeo.default_og_image',
                        'https://example.com/seo/global-default.jpg',
                    ),
            );
    }

    public function test_global_social_toggles_are_shared_authoritatively_when_disabled(): void
    {
        $this->saveSettings(
            enableOpenGraph: false,

            enableTwitterCards: false,
        );

        $product =
            Product::factory()
                ->create([
                    'name' => 'Disabled Social Product',

                    'slug' => 'disabled-social-product',

                    'price' => 64000,

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
                        'storefrontSocialSeo.enable_open_graph',
                        false,
                    )
                    ->where(
                        'storefrontSocialSeo.enable_twitter_cards',
                        false,
                    )
                    ->has(
                        'seo',
                    ),
            );
    }

    private function saveSettings(
        ?string $defaultOgImage = null,
        bool $enableOpenGraph = true,
        bool $enableTwitterCards = true,
        string $twitterCard = 'summary_large_image',
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

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: $defaultOgImage,

                twitterCard: $twitterCard,

                organizationName: 'MWT Store',

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: $enableOpenGraph,

                enableTwitterCards: $enableTwitterCards,

                enableSitemap: true,
            ),
        );
    }
}
