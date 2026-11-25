<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Reviews\Data\StorefrontProductReviewAggregateData;
use App\Domain\Seo\StructuredData\Nodes\AggregateRatingStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class AggregateRatingStructuredDataNodeTest extends TestCase
{
    public function test_it_serializes_real_review_aggregate_values(): void
    {
        $node =
            new AggregateRatingStructuredDataNode(
                aggregate: new StorefrontProductReviewAggregateData(
                    ratingValue: 4.5,

                    reviewCount: 12,
                ),
            );

        self::assertSame(
            [
                '@type' => 'AggregateRating',

                'ratingValue' => 4.5,

                'reviewCount' => 12,

                'bestRating' => 5,

                'worstRating' => 1,
            ],
            $node->toStructuredData(),
        );
    }

    public function test_aggregate_data_rejects_zero_count_and_out_of_range_values(): void
    {
        self::assertFalse(
            (
                new StorefrontProductReviewAggregateData(
                    ratingValue: 4.5,

                    reviewCount: 0,
                )
            )->isValid(),
        );

        self::assertFalse(
            (
                new StorefrontProductReviewAggregateData(
                    ratingValue: 5.5,

                    reviewCount: 2,
                )
            )->isValid(),
        );

        self::assertFalse(
            (
                new StorefrontProductReviewAggregateData(
                    ratingValue: 0.5,

                    reviewCount: 2,
                )
            )->isValid(),
        );
    }
}
