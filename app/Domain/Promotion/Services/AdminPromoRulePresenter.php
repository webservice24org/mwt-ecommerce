<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Services;

use App\Domain\Promotion\Data\ResolvedPromoCode;
use App\Domain\Promotion\Enums\DiscountType;
use App\Models\PromoCode;
use Carbon\CarbonImmutable;

final class AdminPromoRulePresenter
{
    public function __construct(
        private readonly PromoCodeResolver $resolver,
    ) {}

    /**
     * @return array{
     *     discount_type: string,
     *     discount_type_label: string,
     *     discount_value: int,
     *     discount_display: string,
     *     maximum_discount: int|null,
     *     minimum_subtotal: int|null,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     is_active: bool,
     *     is_stackable: bool,
     *     discount_source: string,
     *     maximum_discount_source: string,
     *     minimum_subtotal_source: string,
     *     starts_at_source: string,
     *     ends_at_source: string,
     *     status: string,
     *     status_label: string
     * }
     */
    public function present(
        PromoCode $promoCode,
        ?CarbonImmutable $now = null,
    ): array {
        $promoCode->loadMissing('promotion');

        $resolved = $this->resolver->resolve(
            $promoCode,
        );

        $now ??= CarbonImmutable::now();

        return [
            'discount_type' => $resolved->discountType->value,

            'discount_type_label' => $resolved->discountType->label(),

            'discount_value' => $resolved->discountValue,

            'discount_display' => $this->discountDisplay(
                $resolved,
            ),

            'maximum_discount' => $resolved->maximumDiscount,

            'minimum_subtotal' => $resolved->minimumSubtotal,

            'starts_at' => $resolved->startsAt?->toISOString(),

            'ends_at' => $resolved->endsAt?->toISOString(),

            'is_active' => $resolved->isActive,

            'is_stackable' => $resolved->isStackable,

            'discount_source' => $this->discountSource(
                $promoCode,
            ),

            'maximum_discount_source' => $this->source(
                $promoCode->maximum_discount,
                $promoCode->promotion_id,
            ),

            'minimum_subtotal_source' => $this->source(
                $promoCode->minimum_subtotal,
                $promoCode->promotion_id,
            ),

            'starts_at_source' => $this->source(
                $promoCode->starts_at,
                $promoCode->promotion_id,
            ),

            'ends_at_source' => $this->source(
                $promoCode->ends_at,
                $promoCode->promotion_id,
            ),

            'status' => $this->status(
                $resolved,
                $now,
            ),

            'status_label' => $this->statusLabel(
                $resolved,
                $now,
            ),
        ];
    }

    private function discountDisplay(
        ResolvedPromoCode $resolved,
    ): string {
        if (
            $resolved->discountType ===
            DiscountType::Percentage
        ) {
            return $this->formatPercentage(
                $resolved->discountValue,
            );
        }

        return sprintf(
            'Tk %d',
            $resolved->discountValue,
        );
    }

    private function formatPercentage(
        int $basisPoints,
    ): string {
        $percentage =
            $basisPoints / 100;

        if (
            floor($percentage) ===
            $percentage
        ) {
            return sprintf(
                '%d%%',
                (int) $percentage,
            );
        }

        return rtrim(
            rtrim(
                number_format(
                    $percentage,
                    2,
                    '.',
                    '',
                ),
                '0',
            ),
            '.',
        ).'%';
    }

    private function discountSource(
        PromoCode $promoCode,
    ): string {
        if (
            $promoCode->discount_type !== null &&
            $promoCode->discount_value !== null
        ) {
            return 'promo_code';
        }

        return $promoCode->promotion_id !== null
            ? 'promotion'
            : 'promo_code';
    }

    private function source(
        mixed $value,
        ?int $promotionId,
    ): string {
        if ($value !== null) {
            return 'promo_code';
        }

        return $promotionId !== null
            ? 'promotion'
            : 'none';
    }

    private function status(
        ResolvedPromoCode $resolved,
        CarbonImmutable $now,
    ): string {
        if (! $resolved->isActive) {
            return 'inactive';
        }

        if (
            $resolved->startsAt !== null &&
            $now->lt($resolved->startsAt)
        ) {
            return 'scheduled';
        }

        if (
            $resolved->endsAt !== null &&
            $now->gt($resolved->endsAt)
        ) {
            return 'expired';
        }

        return 'active';
    }

    private function statusLabel(
        ResolvedPromoCode $resolved,
        CarbonImmutable $now,
    ): string {
        return match (
            $this->status(
                $resolved,
                $now,
            )
        ) {
            'inactive' => 'Inactive',
            'scheduled' => 'Scheduled',
            'expired' => 'Expired',
            default => 'Active',
        };
    }
}
