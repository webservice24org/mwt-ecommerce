<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Services;

use App\Models\Promotion;

final class PromotionDeletionGuard
{
    public function linkedPromoCodeCount(
        Promotion $promotion,
    ): int {
        return $promotion
            ->promoCodes()
            ->count();
    }

    public function canDelete(
        Promotion $promotion,
    ): bool {
        return ! $promotion
            ->promoCodes()
            ->exists();
    }

    public function blockReason(
        int $linkedPromoCodeCount,
    ): ?string {
        if ($linkedPromoCodeCount === 0) {
            return null;
        }

        $label =
            $linkedPromoCodeCount === 1
            ? 'promo code is'
            : 'promo codes are';

        return sprintf(
            'This promotion cannot be deleted because %d linked %s still using it. Detach the promo code%s first.',
            $linkedPromoCodeCount,
            $label,
            $linkedPromoCodeCount === 1
            ? ''
            : 's',
        );
    }
}
