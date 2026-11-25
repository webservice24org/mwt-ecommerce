<?php

declare(strict_types=1);

namespace Tests\Feature\Reviews;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Domain\Reviews\Queries\StorefrontProductReviewAggregateQuery;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontProductReviewAggregateQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_only_approved_reviews_for_the_requested_product(): void
    {
        $product =
            Product::factory()
                ->create();

        $otherProduct =
            Product::factory()
                ->create();

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

            rating: 2,

            status: ProductReviewStatus::Rejected,
        );

        $this->review(
            product: $otherProduct,

            rating: 1,

            status: ProductReviewStatus::Approved,
        );

        $aggregate =
            app(
                StorefrontProductReviewAggregateQuery::class,
            )->get(
                product: $product,
            );

        self::assertNotNull(
            $aggregate,
        );

        self::assertSame(
            2,
            $aggregate->reviewCount,
        );

        self::assertSame(
            4.5,
            $aggregate->ratingValue,
        );

        self::assertSame(
            5,
            $aggregate->bestRating,
        );

        self::assertSame(
            1,
            $aggregate->worstRating,
        );
    }

    public function test_it_returns_null_when_there_are_no_approved_reviews(): void
    {
        $product =
            Product::factory()
                ->create();

        $this->review(
            product: $product,

            rating: 5,

            status: ProductReviewStatus::Pending,
        );

        $this->review(
            product: $product,

            rating: 1,

            status: ProductReviewStatus::Rejected,
        );

        self::assertNull(
            app(
                StorefrontProductReviewAggregateQuery::class,
            )->get(
                product: $product,
            ),
        );
    }

    public function test_average_is_rounded_to_two_decimal_places(): void
    {
        $product =
            Product::factory()
                ->create();

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

            rating: 3,

            status: ProductReviewStatus::Approved,
        );

        $aggregate =
            app(
                StorefrontProductReviewAggregateQuery::class,
            )->get(
                product: $product,
            );

        self::assertNotNull(
            $aggregate,
        );

        self::assertSame(
            4.0,
            $aggregate->ratingValue,
        );

        self::assertSame(
            3,
            $aggregate->reviewCount,
        );
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
}
