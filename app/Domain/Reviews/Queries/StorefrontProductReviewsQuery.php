<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Queries;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class StorefrontProductReviewsQuery
{
    /**
     * @return array{
     *     summary: array{
     *         average_rating: float,
     *         review_count: int,
     *         distribution: array<int, array{
     *             rating: int,
     *             count: int,
     *             percentage: float
     *         }>
     *     },
     *     reviews: array{
     *         data: list<array{
     *             id: int,
     *             rating: int,
     *             title: string|null,
     *             body: string,
     *             is_verified_purchase: bool,
     *             author_name: string,
     *             created_at: string|null
     *         }>,
     *         current_page: int,
     *         last_page: int,
     *         per_page: int,
     *         total: int
     *     }
     * }
     */
    public function get(
        Product $product,
        int $perPage = 10,
    ): array {
        /*
         * ==================================================
         * APPROVED-ONLY PUBLIC BOUNDARY
         * ==================================================
         */
        $approvedQuery =
            ProductReview::query()
                ->where(
                    'product_id',
                    $product->id,
                )
                ->where(
                    'status',
                    ProductReviewStatus::Approved
                        ->value,
                );

        $reviewCount =
            (clone $approvedQuery)
                ->count();

        $averageRating =
            $reviewCount > 0
            ? (float) (
                (clone $approvedQuery)
                    ->avg('rating') ??
                0
            )
            : 0.0;

        /*
         * ==================================================
         * RATING DISTRIBUTION
         * ==================================================
         */
        $ratingCounts =
            (clone $approvedQuery)
                ->selectRaw(
                    'rating, COUNT(*) as aggregate',
                )
                ->groupBy(
                    'rating',
                )
                ->pluck(
                    'aggregate',
                    'rating',
                );

        $distribution = [];

        for (
            $rating = 5;
            $rating >= 1;
            $rating--
        ) {
            $count =
                (int) (
                    $ratingCounts[
                        $rating
                    ] ??
                    0
                );

            $distribution[] = [
                'rating' => $rating,

                'count' => $count,

                'percentage' => $reviewCount > 0
                    ? round(
                        (
                            $count /
                            $reviewCount
                        ) * 100,
                        1,
                    )
                    : 0.0,
            ];
        }

        /*
         * ==================================================
         * PAGINATED APPROVED REVIEWS
         * ==================================================
         *
         * Separate page name prevents collisions with
         * other paginated data on the product page.
         */
        $paginator =
            ProductReview::query()
                ->with([
                    'user:id,name',
                ])
                ->where(
                    'product_id',
                    $product->id,
                )
                ->where(
                    'status',
                    ProductReviewStatus::Approved
                        ->value,
                )
                ->latest(
                    'created_at',
                )
                ->latest(
                    'id',
                )
                ->paginate(
                    perPage: $perPage,

                    pageName: 'reviews_page',
                );

        return [
            'summary' => [
                'average_rating' => round(
                    $averageRating,
                    1,
                ),

                'review_count' => $reviewCount,

                'distribution' => $distribution,
            ],

            'reviews' => $this->reviews(
                $paginator,
            ),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, ProductReview>  $paginator
     * @return array{
     *     data: list<array{
     *         id: int,
     *         rating: int,
     *         title: string|null,
     *         body: string,
     *         is_verified_purchase: bool,
     *         author_name: string,
     *         created_at: string|null
     *     }>,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int
     * }
     */
    private function reviews(
        LengthAwarePaginator $paginator,
    ): array {
        $data =
            $paginator
                ->getCollection()
                ->map(
                    static function (ProductReview $review): array {
                        return [
                            'id' => $review->id,

                            'rating' => $review->rating,

                            'title' => $review->title,

                            'body' => $review->body,

                            'is_verified_purchase' => $review
                                ->is_verified_purchase,

                            /*
                             * Never expose customer email
                             * or other private profile data.
                             */
                            'author_name' => $review
                                ->user
                                ->name,

                            'created_at' => $review
                                ->created_at
                                ?->toISOString(),
                        ];
                    },
                )
                ->values()
                ->all();

        return [
            'data' => $data,

            'current_page' => $paginator
                ->currentPage(),

            'last_page' => $paginator
                ->lastPage(),

            'per_page' => $paginator
                ->perPage(),

            'total' => $paginator
                ->total(),
        ];
    }
}
