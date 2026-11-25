<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Reviews\Data\StorefrontProductReviewAggregateData;

final readonly class AggregateRatingStructuredDataNode
{
    public function __construct(
        private StorefrontProductReviewAggregateData $aggregate,
    ) {}

    /**
     * @return array<string, int|float|string>
     */
    public function toStructuredData(): array
    {
        return [
            '@type' => 'AggregateRating',
            'ratingValue' => $this->aggregate->ratingValue,
            'reviewCount' => $this->aggregate->reviewCount,
            'bestRating' => $this->aggregate->bestRating,
            'worstRating' => $this->aggregate->worstRating,
        ];
    }
}
