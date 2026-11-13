<?php

declare(strict_types=1);

namespace Tests\Feature\Reviews;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Domain\Reviews\Queries\StorefrontProductReviewsQuery;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontProductReviewsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_reviews_are_public_and_aggregated(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,
            ]);

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 3,
            ]);

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,

                'status' => ProductReviewStatus::Pending,
            ]);

        ProductReview::factory()
            ->rejected()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            2,
            $result['summary']['review_count'],
        );

        $this->assertSame(
            4.0,
            $result['summary']['average_rating'],
        );

        $this->assertCount(
            2,
            $result['reviews']['data'],
        );

        $ratings =
            collect(
                $result['reviews']['data'],
            )
                ->pluck(
                    'rating',
                )
                ->sort()
                ->values()
                ->all();

        $this->assertSame(
            [
                3,
                5,
            ],
            $ratings,
        );
    }

    public function test_rating_distribution_uses_only_approved_reviews(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->count(3)
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,
            ]);

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 4,
            ]);

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,

                'status' => ProductReviewStatus::Pending,
            ]);

        ProductReview::factory()
            ->rejected()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $distribution =
            collect(
                $result['summary']['distribution'],
            )
                ->keyBy(
                    'rating',
                );

        $this->assertCount(
            5,
            $distribution,
        );

        $this->assertSame(
            3,
            $distribution[5]['count'],
        );

        $this->assertSame(
            75.0,
            $distribution[5]['percentage'],
        );

        $this->assertSame(
            1,
            $distribution[4]['count'],
        );

        $this->assertSame(
            25.0,
            $distribution[4]['percentage'],
        );

        $this->assertSame(
            0,
            $distribution[3]['count'],
        );

        $this->assertSame(
            0.0,
            $distribution[3]['percentage'],
        );

        $this->assertSame(
            0,
            $distribution[2]['count'],
        );

        $this->assertSame(
            0.0,
            $distribution[2]['percentage'],
        );

        $this->assertSame(
            0,
            $distribution[1]['count'],
        );

        $this->assertSame(
            0.0,
            $distribution[1]['percentage'],
        );
    }

    public function test_no_approved_reviews_returns_zero_summary(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,

                'status' => ProductReviewStatus::Pending,
            ]);

        ProductReview::factory()
            ->rejected()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            0,
            $result['summary']['review_count'],
        );

        $this->assertSame(
            0.0,
            $result['summary']['average_rating'],
        );

        $this->assertSame(
            [],
            $result['reviews']['data'],
        );

        $this->assertSame(
            0,
            $result['reviews']['total'],
        );

        foreach (
            $result['summary']['distribution'] as $distribution
        ) {
            $this->assertSame(
                0,
                $distribution[
                    'count'
                ],
            );

            $this->assertSame(
                0.0,
                $distribution[
                    'percentage'
                ],
            );
        }
    }

    public function test_reviews_for_other_products_are_excluded(): void
    {
        $product =
            Product::factory()
                ->create();

        $otherProduct =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,

                'title' => 'Expected review',
            ]);

        ProductReview::factory()
            ->count(4)
            ->approved()
            ->create([
                'product_id' => $otherProduct->id,

                'rating' => 1,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            1,
            $result['summary']['review_count'],
        );

        $this->assertSame(
            5.0,
            $result['summary']['average_rating'],
        );

        $this->assertCount(
            1,
            $result['reviews']['data'],
        );

        $this->assertSame(
            'Expected review',
            $result['reviews']['data'][0]['title'],
        );
    }

    public function test_pending_review_content_is_never_exposed(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,

                'title' => 'Public review',

                'body' => 'This approved review may be displayed publicly.',
            ]);

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,

                'title' => 'PRIVATE PENDING TITLE',

                'body' => 'PRIVATE PENDING BODY',

                'status' => ProductReviewStatus::Pending,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $encoded =
            json_encode(
                $result,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringContainsString(
            'Public review',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE PENDING TITLE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE PENDING BODY',
            $encoded,
        );
    }

    public function test_rejected_review_content_is_never_exposed(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 4,

                'title' => 'Visible title',

                'body' => 'Visible approved review body.',
            ]);

        ProductReview::factory()
            ->rejected()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,

                'title' => 'PRIVATE REJECTED TITLE',

                'body' => 'PRIVATE REJECTED BODY',

                'moderation_note' => 'PRIVATE MODERATION NOTE',
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $encoded =
            json_encode(
                $result,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            'PRIVATE REJECTED TITLE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE REJECTED BODY',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE MODERATION NOTE',
            $encoded,
        );
    }

    public function test_public_review_payload_exposes_only_expected_fields(): void
    {
        $product =
            Product::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Review Customer',

                    'email' => 'private-review@example.com',
                ]);

        $review =
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'rating' => 5,

                    'title' => 'Great purchase',

                    'body' => 'This is a useful approved review for the public product page.',

                    'is_verified_purchase' => true,

                    'moderation_note' => 'This is private moderation metadata.',
                ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $publicReview =
            $result['reviews']['data'][0];

        $this->assertSame(
            [
                'id',
                'rating',
                'title',
                'body',
                'is_verified_purchase',
                'author_name',
                'created_at',
            ],
            array_keys(
                $publicReview,
            ),
        );

        $this->assertSame(
            $review->id,
            $publicReview['id'],
        );

        $this->assertSame(
            'Review Customer',
            $publicReview[
                'author_name'
            ],
        );

        $this->assertTrue(
            $publicReview[
                'is_verified_purchase'
            ],
        );

        $encoded =
            json_encode(
                $result,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            'private-review@example.com',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'This is private moderation metadata.',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'moderated_by',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'moderation_note',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'verified_order_id',
            $encoded,
        );
    }

    public function test_verified_purchase_flag_is_exposed_for_public_review(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,

                'is_verified_purchase' => true,
            ]);

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 4,

                'is_verified_purchase' => false,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $flags =
            collect(
                $result['reviews']['data'],
            )
                ->pluck(
                    'is_verified_purchase',
                )
                ->sort()
                ->values()
                ->all();

        $this->assertSame(
            [
                false,
                true,
            ],
            $flags,
        );
    }

    public function test_newest_approved_review_is_listed_first(): void
    {
        $product =
            Product::factory()
                ->create();

        $oldest =
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'rating' => 3,

                    'title' => 'Oldest review',

                    'created_at' => '2026-10-01 10:00:00',

                    'updated_at' => '2026-10-01 10:00:00',
                ]);

        $newest =
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'rating' => 5,

                    'title' => 'Newest review',

                    'created_at' => '2026-11-01 10:00:00',

                    'updated_at' => '2026-11-01 10:00:00',
                ]);

        $middle =
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'rating' => 4,

                    'title' => 'Middle review',

                    'created_at' => '2026-10-15 10:00:00',

                    'updated_at' => '2026-10-15 10:00:00',
                ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $ids =
            collect(
                $result['reviews']['data'],
            )
                ->pluck(
                    'id',
                )
                ->all();

        $this->assertSame(
            [
                $newest->id,
                $middle->id,
                $oldest->id,
            ],
            $ids,
        );
    }

    public function test_reviews_are_paginated_using_requested_per_page_value(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->count(12)
            ->approved()
            ->create([
                'product_id' => $product->id,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,

                perPage: 5,
            );

        $this->assertCount(
            5,
            $result['reviews']['data'],
        );

        $this->assertSame(
            1,
            $result['reviews']['current_page'],
        );

        $this->assertSame(
            3,
            $result['reviews']['last_page'],
        );

        $this->assertSame(
            5,
            $result['reviews']['per_page'],
        );

        $this->assertSame(
            12,
            $result['reviews']['total'],
        );

        /*
         * Summary must represent all approved reviews,
         * not only the current pagination page.
         */
        $this->assertSame(
            12,
            $result['summary']['review_count'],
        );
    }

    public function test_reviews_page_query_parameter_selects_review_pagination_page(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->count(12)
            ->approved()
            ->create([
                'product_id' => $product->id,
            ]);

        request()
            ->query
            ->set(
                'reviews_page',
                2,
            );

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,

                perPage: 5,
            );

        $this->assertSame(
            2,
            $result['reviews']['current_page'],
        );

        $this->assertCount(
            5,
            $result['reviews']['data'],
        );

        $this->assertSame(
            3,
            $result['reviews']['last_page'],
        );

        $this->assertSame(
            12,
            $result['reviews']['total'],
        );
    }

    public function test_average_rating_is_rounded_to_one_decimal_place(): void
    {
        $product =
            Product::factory()
                ->create();

        foreach (
            [
                5,
                5,
                4,
            ] as $rating
        ) {
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'rating' => $rating,
                ]);
        }

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        /*
         * 14 / 3 = 4.666...
         */
        $this->assertSame(
            4.7,
            $result['summary']['average_rating'],
        );
    }

    public function test_distribution_percentages_are_rounded_to_one_decimal_place(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->count(2)
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,
            ]);

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 4,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $distribution =
            collect(
                $result['summary']['distribution'],
            )
                ->keyBy(
                    'rating',
                );

        $this->assertSame(
            66.7,
            $distribution[5]['percentage'],
        );

        $this->assertSame(
            33.3,
            $distribution[4]['percentage'],
        );
    }

    public function test_distribution_is_returned_from_five_stars_down_to_one_star(): void
    {
        $product =
            Product::factory()
                ->create();

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $ratings =
            collect(
                $result['summary']['distribution'],
            )
                ->pluck(
                    'rating',
                )
                ->all();

        $this->assertSame(
            [
                5,
                4,
                3,
                2,
                1,
            ],
            $ratings,
        );
    }
}
