<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Queries;

use App\Domain\Reviews\Services\ProductReviewEligibilityService;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;

final readonly class StorefrontProductReviewStateQuery
{
    public function __construct(
        private ProductReviewEligibilityService $eligibility,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(
        ?User $user,
        Product $product,
    ): array {
        if ($user === null) {
            return [
                'authenticated' => false,

                'eligibility' => null,

                'review' => null,
            ];
        }

        $review =
            ProductReview::query()
                ->where(
                    'product_id',
                    $product->id,
                )
                ->where(
                    'user_id',
                    $user->id,
                )
                ->first();

        if (
            $review instanceof ProductReview
        ) {
            return [
                'authenticated' => true,

                'eligibility' => [
                    'eligible' => false,

                    'reason' => 'You have already reviewed this product.',
                ],

                'review' => [
                    'id' => $review->id,

                    'rating' => $review->rating,

                    'title' => $review->title,

                    'body' => $review->body,

                    'status' => $review->status
                        ->value,

                    'status_label' => $review->status
                        ->label(),

                    'is_verified_purchase' => $review
                        ->is_verified_purchase,
                ],
            ];
        }

        $eligibility =
            $this->eligibility
                ->check(
                    user: $user,

                    product: $product,
                );

        return [
            'authenticated' => true,

            'eligibility' => [
                'eligible' => $eligibility
                    ->eligible,

                'reason' => $eligibility
                    ->reason,
            ],

            'review' => null,
        ];
    }
}
