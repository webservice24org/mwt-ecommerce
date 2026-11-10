<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Data;

use App\Models\PromoCodeUsage;

final readonly class PromoRedemptionResult
{
    public function __construct(
        public PromoCodeUsage $usage,
        public int $discountAmount,
        public bool $redeemedNow,
    ) {}
}
