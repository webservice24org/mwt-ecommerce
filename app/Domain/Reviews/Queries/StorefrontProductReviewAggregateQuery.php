<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Queries;

use App\Domain\Reviews\Data\StorefrontProductReviewAggregateData;
use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;

final class StorefrontProductReviewAggregateQuery
{
    public function get(
        Product $product,
    ): ?StorefrontProductReviewAggregateData {
        /*
         * Keep this visibility contract identical to the
         * existing storefront review query:
         *
         *     ProductReviewStatus::Approved
         *
         * Pending and rejected reviews must never contribute
         * to public AggregateRating structured data.
         */
        $row =
            ProductReview::query()
                ->where(
                    'product_id',
                    $product->getKey(),
                )
                ->where(
                    'status',
                    ProductReviewStatus::Approved
                        ->value,
                )
                ->selectRaw(
                    'COUNT(*) AS review_count, '
                    .'AVG(rating) AS average_rating',
                )
                ->toBase()
                ->first();

        if ($row === null) {
            return null;
        }

        $values =
            get_object_vars(
                $row,
            );

        $reviewCount =
            (int) (
                $values['review_count']
                ?? 0
            );

        $averageRating =
            $values['average_rating']
            ?? null;

        if (
            $reviewCount < 1
            || ! is_numeric(
                $averageRating,
            )
        ) {
            return null;
        }

        $aggregate =
            new StorefrontProductReviewAggregateData(
                ratingValue: round(
                    (float) $averageRating,
                    2,
                ),

                reviewCount: $reviewCount,
            );

        return $aggregate->isValid()
            ? $aggregate
            : null;
    }
}
