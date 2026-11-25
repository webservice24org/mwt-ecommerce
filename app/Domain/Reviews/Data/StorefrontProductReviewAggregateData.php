<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Data;

final readonly class StorefrontProductReviewAggregateData
{
    public const BEST_RATING = 5;

    public const WORST_RATING = 1;

    public function __construct(
        public float $ratingValue,
        public int $reviewCount,
        public int $bestRating = self::BEST_RATING,
        public int $worstRating = self::WORST_RATING,
    ) {}

    public function isValid(): bool
    {
        return $this->reviewCount > 0
            && is_finite(
                $this->ratingValue,
            )
            && $this->bestRating >
                $this->worstRating
            && $this->ratingValue >=
                $this->worstRating
            && $this->ratingValue <=
                $this->bestRating;
    }
}
