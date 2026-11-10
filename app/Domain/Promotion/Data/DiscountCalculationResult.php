<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Data;

final readonly class DiscountCalculationResult
{
    public function __construct(
        public int $subtotal,
        public int $requestedDiscount,
        public int $discountAmount,
        public int $remainingSubtotal,
        public bool $cappedByMaximumDiscount,
        public bool $cappedBySubtotal,
    ) {}
}
