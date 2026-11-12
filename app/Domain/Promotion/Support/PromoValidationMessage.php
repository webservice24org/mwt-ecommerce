<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Support;

final class PromoValidationMessage
{
    public static function forReason(
        ?string $reason,
    ): string {
        return match ($reason) {
            'inactive' => 'This promo code is inactive.',

            'not_started' => 'This promo code is not active yet.',

            'expired' => 'This promo code has expired.',

            'minimum_subtotal' => 'Your cart does not meet the minimum subtotal required for this promo code.',

            'usage_limit' => 'This promo code has reached its usage limit.',

            'usage_limit_per_customer' => 'You have already reached the usage limit for this promo code.',

            default => 'This promo code cannot be applied to your cart.',
        };
    }
}
