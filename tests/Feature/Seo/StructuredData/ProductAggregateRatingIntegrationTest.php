<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\StructuredData;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProductAggregateRatingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.currency' => 'BDT',

            'app.url' => 'https://example.com',
        ]);

        $this->saveSeoSettings();
    }

    public function test_simple_product_exposes_only_approved_review_aggregate(): void
    {
        $product =
            $this->simpleProduct(
                slug: 'rated-charger',
            );

        $this->review(
            product: $product,

            rating: 5,

            status: ProductReviewStatus::Approved,
        );

        $this->review(
            product: $product,

            rating: 4,

            status: ProductReviewStatus::Approved,
        );

        $this->review(
            product: $product,

            rating: 1,

            status: ProductReviewStatus::Pending,
        );

        $this->review(
            product: $product,

            rating: 1,

            status: ProductReviewStatus::Rejected,
        );

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'structuredData.@graph.1.@type',
                        'Product',
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.@type',
                        'AggregateRating',
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.ratingValue',
                        4.5,
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.reviewCount',
                        2,
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.bestRating',
                        5,
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.worstRating',
                        1,
                    )
                    ->where(
                        'reviews.summary.average_rating',
                        4.5,
                    )
                    ->where(
                        'reviews.summary.review_count',
                        2,
                    ),
            );
    }

    public function test_product_without_approved_reviews_omits_aggregate_rating(): void
    {
        $product =
            $this->simpleProduct(
                slug: 'unrated-charger',
            );

        $this->review(
            product: $product,

            rating: 5,

            status: ProductReviewStatus::Pending,
        );

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'structuredData.@graph.1.@type',
                        'Product',
                    )
                    ->missing(
                        'structuredData.@graph.1.aggregateRating',
                    )
                    ->where(
                        'reviews.summary.review_count',
                        0,
                    ),
            );
    }

    public function test_variable_product_places_product_level_rating_once_on_product_group(): void
    {
        $product =
            Product::factory()
                ->variable()
                ->create([
                    'name' => 'Rated Cable',

                    'slug' => 'rated-cable',

                    'sku' => 'RATED-CABLE-GROUP',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),

                    'track_inventory' => true,

                    'stock_quantity' => 0,
                ]);

        ProductVariant::factory()
            ->create([
                'product_id' => $product->id,

                'sku' => 'RATED-CABLE-BLACK',

                'name' => 'Black',

                'price' => 85000,

                'stock_quantity' => 5,

                'is_active' => true,

                'is_default' => true,

                'position' => 0,
            ]);

        $this->review(
            product: $product,

            rating: 5,

            status: ProductReviewStatus::Approved,
        );

        $this->review(
            product: $product,

            rating: 3,

            status: ProductReviewStatus::Approved,
        );

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'structuredData.@graph.1.@type',
                        'ProductGroup',
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.ratingValue',
                        4,
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.reviewCount',
                        2,
                    )
                    ->missing(
                        'structuredData.@graph.1.hasVariant.0.aggregateRating',
                    ),
            );
    }

    public function test_disabled_structured_data_stays_null_even_when_approved_reviews_exist(): void
    {
        $this->saveSeoSettings(
            enabled: false,
        );

        $product =
            $this->simpleProduct(
                slug: 'schema-disabled-rated-product',
            );

        $this->review(
            product: $product,

            rating: 5,

            status: ProductReviewStatus::Approved,
        );

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'structuredData',
                    null,
                ),
            );
    }

    private function simpleProduct(
        string $slug,
    ): Product {
        return Product::factory()
            ->create([
                'name' => 'Rated Charger',

                'slug' => $slug,

                'sku' => strtoupper(
                    str_replace(
                        '-',
                        '_',
                        $slug,
                    ),
                ),

                'price' => 485000,

                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->subMinute(),

                'track_inventory' => true,

                'stock_quantity' => 10,
            ]);
    }

    private function review(
        Product $product,
        int $rating,
        ProductReviewStatus $status,
    ): ProductReview {
        return ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'user_id' => User::factory()
                    ->create()
                    ->id,

                'rating' => $rating,

                'status' => $status,
            ]);
    }

    private function saveSeoSettings(
        bool $enabled = true,
    ): void {
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

                enableStructuredData: $enabled,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
