<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Data;

final readonly class ProductReviewEligibilityData
{
    public function __construct(
        public bool $eligible,
        public bool $verifiedPurchase,
        public bool $alreadyReviewed,
        public ?int $verifiedOrderId,
        public ?string $reason,
    ) {}

    public static function eligible(
        int $verifiedOrderId,
    ): self {
        return new self(
            eligible: true,
            verifiedPurchase: true,
            alreadyReviewed: false,
            verifiedOrderId: $verifiedOrderId,
            reason: null,
        );
    }

    public static function alreadyReviewed(): self
    {
        return new self(
            eligible: false,
            verifiedPurchase: false,
            alreadyReviewed: true,
            verifiedOrderId: null,
            reason: 'You have already reviewed this product.',
        );
    }

    public static function noVerifiedPurchase(): self
    {
        return new self(
            eligible: false,
            verifiedPurchase: false,
            alreadyReviewed: false,
            verifiedOrderId: null,
            reason: 'You can review this product after a completed delivery.',
        );
    }
}
