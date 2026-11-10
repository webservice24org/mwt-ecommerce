<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Data;

use App\Domain\Promotion\Enums\DiscountType;
use Carbon\CarbonImmutable;

final readonly class ResolvedPromoCode
{
    public function __construct(
        public int $promoCodeId,
        public string $code,
        public DiscountType $discountType,
        public int $discountValue,
        public ?int $maximumDiscount,
        public ?int $minimumSubtotal,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public bool $isActive,
        public bool $isStackable,
    ) {}
}
