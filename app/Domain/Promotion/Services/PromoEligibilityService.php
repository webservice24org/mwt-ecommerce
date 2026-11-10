<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Services;

use App\Domain\Promotion\Data\ResolvedPromoCode;
use App\Domain\Promotion\Results\PromoEligibilityResult;
use Carbon\CarbonImmutable;

final class PromoEligibilityService
{
    public function check(
        ResolvedPromoCode $promo,
        int $subtotal,
        ?CarbonImmutable $now = null,
    ): PromoEligibilityResult {
        $now ??= CarbonImmutable::now();

        if (! $promo->isActive) {
            return PromoEligibilityResult::rejected(
                'This promo code is inactive.',
            );
        }

        if (
            $promo->startsAt !== null &&
            $now->lt($promo->startsAt)
        ) {
            return PromoEligibilityResult::rejected(
                'This promo code is not active yet.',
            );
        }

        if (
            $promo->endsAt !== null &&
            $now->gt($promo->endsAt)
        ) {
            return PromoEligibilityResult::rejected(
                'This promo code has expired.',
            );
        }

        if (
            $promo->minimumSubtotal !== null &&
            $subtotal < $promo->minimumSubtotal
        ) {
            return PromoEligibilityResult::rejected(
                'The minimum order amount for this promo code has not been reached.',
            );
        }

        return PromoEligibilityResult::eligible();
    }
}
