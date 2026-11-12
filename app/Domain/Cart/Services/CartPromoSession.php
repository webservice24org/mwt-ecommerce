<?php

declare(strict_types=1);

namespace App\Domain\Cart\Services;

use InvalidArgumentException;

final class CartPromoSession
{
    /*
     * IMPORTANT:
     *
     * Do not use:
     *
     * storefront.cart.promo_code_id
     *
     * because Laravel session dot notation would
     * insert promo_code_id inside storefront.cart,
     * which is reserved exclusively for cart items.
     */
    private const SESSION_KEY =
        'storefront.selected_promo_code_id';

    public function promoCodeId(): ?int
    {
        $value =
            session()->get(
                self::SESSION_KEY,
            );

        if (
            is_int($value)
            && $value > 0
        ) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        return null;
    }

    public function put(
        int $promoCodeId,
    ): void {
        if ($promoCodeId <= 0) {
            throw new InvalidArgumentException(
                'Promo code ID must be positive.',
            );
        }

        session()->put(
            self::SESSION_KEY,
            $promoCodeId,
        );
    }

    public function forget(): void
    {
        session()->forget(
            self::SESSION_KEY,
        );
    }

    public function has(): bool
    {
        return $this->promoCodeId() !== null;
    }
}
