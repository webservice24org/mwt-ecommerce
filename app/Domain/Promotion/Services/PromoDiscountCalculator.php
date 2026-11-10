<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Services;

use App\Domain\Promotion\Data\DiscountCalculationResult;
use App\Domain\Promotion\Data\ResolvedPromoCode;
use App\Domain\Promotion\Enums\DiscountType;
use InvalidArgumentException;

final class PromoDiscountCalculator
{
    private const BASIS_POINTS_DENOMINATOR = 10_000;

    public function calculate(
        ResolvedPromoCode $promo,
        int $subtotal,
    ): DiscountCalculationResult {
        if ($subtotal < 0) {
            throw new InvalidArgumentException(
                'Subtotal cannot be negative.',
            );
        }

        $this->validatePromo($promo);

        if ($subtotal === 0) {
            return new DiscountCalculationResult(
                subtotal: 0,
                requestedDiscount: 0,
                discountAmount: 0,
                remainingSubtotal: 0,
                cappedByMaximumDiscount: false,
                cappedBySubtotal: false,
            );
        }

        $requestedDiscount = match ($promo->discountType) {
            DiscountType::Fixed => $promo->discountValue,

            DiscountType::Percentage => $this->percentageDiscount(
                subtotal: $subtotal,
                basisPoints: $promo->discountValue,
            ),
        };

        $discountAfterMaximumCap = $requestedDiscount;
        $cappedByMaximumDiscount = false;

        if (
            $promo->maximumDiscount !== null &&
            $discountAfterMaximumCap > $promo->maximumDiscount
        ) {
            $discountAfterMaximumCap =
                $promo->maximumDiscount;

            $cappedByMaximumDiscount = true;
        }

        $discountAmount = min(
            $discountAfterMaximumCap,
            $subtotal,
        );

        $cappedBySubtotal =
            $discountAfterMaximumCap > $subtotal;

        return new DiscountCalculationResult(
            subtotal: $subtotal,
            requestedDiscount: $requestedDiscount,
            discountAmount: $discountAmount,
            remainingSubtotal: $subtotal - $discountAmount,
            cappedByMaximumDiscount: $cappedByMaximumDiscount,
            cappedBySubtotal: $cappedBySubtotal,
        );
    }

    private function percentageDiscount(
        int $subtotal,
        int $basisPoints,
    ): int {
        return intdiv(
            $subtotal * $basisPoints,
            self::BASIS_POINTS_DENOMINATOR,
        );
    }

    private function validatePromo(
        ResolvedPromoCode $promo,
    ): void {
        if ($promo->discountValue < 0) {
            throw new InvalidArgumentException(
                'Discount value cannot be negative.',
            );
        }

        if (
            $promo->discountType ===
            DiscountType::Percentage &&
            $promo->discountValue >
            self::BASIS_POINTS_DENOMINATOR
        ) {
            throw new InvalidArgumentException(
                'Percentage discount cannot exceed 100%.',
            );
        }

        if (
            $promo->maximumDiscount !== null &&
            $promo->maximumDiscount < 0
        ) {
            throw new InvalidArgumentException(
                'Maximum discount cannot be negative.',
            );
        }
    }
}
