<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Services;

use App\Domain\Promotion\Data\ResolvedPromoCode;
use App\Domain\Promotion\Enums\DiscountType;
use App\Models\PromoCode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use LogicException;

final class PromoCodeResolver
{
    public function resolve(PromoCode $promoCode): ResolvedPromoCode
    {
        $promoCode->loadMissing('promotion');

        $promotion = $promoCode->promotion;

        $promoCodeDiscountType = $this->discountType(
            $promoCode->getAttribute('discount_type'),
        );

        $promotionDiscountType = $promotion === null
            ? null
            : $this->discountType(
                $promotion->getAttribute('discount_type'),
            );

        $discountType =
            $promoCodeDiscountType ??
            $promotionDiscountType;

        if ($discountType === null) {
            throw new LogicException(
                'Promo code has no resolvable discount type.',
            );
        }

        $discountValue =
            $promoCode->discount_value ??
            $promotion?->discount_value;

        if ($discountValue === null) {
            throw new LogicException(
                'Promo code has no resolvable discount value.',
            );
        }

        $startsAt = $this->immutableDate(
            $promoCode->getAttribute('starts_at')
            ?? $promotion?->getAttribute('starts_at'),
        );

        $endsAt = $this->immutableDate(
            $promoCode->getAttribute('ends_at')
            ?? $promotion?->getAttribute('ends_at'),
        );

        return new ResolvedPromoCode(
            promoCodeId: $promoCode->id,
            code: $promoCode->code,
            discountType: $discountType,
            discountValue: $discountValue,
            maximumDiscount: $promoCode->maximum_discount ??
            $promotion?->maximum_discount,
            minimumSubtotal: $promoCode->minimum_subtotal ??
            $promotion?->minimum_subtotal,
            startsAt: $startsAt,
            endsAt: $endsAt,
            isActive: $promoCode->is_active &&
            (
                $promotion === null ||
                $promotion->is_active
            ),
            isStackable: $promoCode->is_stackable &&
            (
                $promotion === null ||
                $promotion->is_stackable
            ),
        );
    }

    private function discountType(
        mixed $value,
    ): ?DiscountType {
        if ($value instanceof DiscountType) {
            return $value;
        }

        if (is_string($value)) {
            return DiscountType::tryFrom($value);
        }

        return null;
    }

    private function immutableDate(
        mixed $value,
    ): ?CarbonImmutable {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value);
        }

        if (is_string($value)) {
            return CarbonImmutable::parse($value);
        }

        throw new LogicException(
            'Promo date value is invalid.',
        );
    }
}
