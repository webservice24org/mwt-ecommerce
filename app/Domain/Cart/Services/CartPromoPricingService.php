<?php

declare(strict_types=1);

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Data\CartPricingSummaryData;
use App\Domain\Cart\Data\CartPromoData;
use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Promotion\Data\PromoValidationContext;
use App\Domain\Promotion\Services\PromoCodeResolver;
use App\Domain\Promotion\Services\PromoDiscountCalculator;
use App\Domain\Promotion\Services\PromoValidationService;
use App\Models\PromoCode;
use Carbon\CarbonImmutable;

final readonly class CartPromoPricingService
{
    public function __construct(
        private CartPromoSession $promoSession,
        private PromoValidationService $validationService,
        private PromoCodeResolver $resolver,
        private PromoDiscountCalculator $calculator,
    ) {}

    public function calculate(
        CartSummaryData $cart,
        ?int $userId,
        ?CarbonImmutable $now = null,
    ): CartPricingSummaryData {
        /*
         * A cart with no monetary value cannot
         * retain an applied promo.
         *
         * We intentionally use subtotal rather than
         * itemCount here because this service can also
         * be unit-tested with synthetic pricing-only
         * CartSummaryData instances.
         */
        if ($cart->subtotal <= 0) {
            $this->promoSession
                ->forget();

            return $this->withoutPromo(
                $cart,
            );
        }

        $promoCodeId =
            $this->promoSession
                ->promoCodeId();

        if ($promoCodeId === null) {
            return $this->withoutPromo(
                $cart,
            );
        }

        $promoCode =
            PromoCode::query()
                ->find(
                    $promoCodeId,
                );

        if (
            ! $promoCode instanceof PromoCode
        ) {
            $this->promoSession
                ->forget();

            return $this->withoutPromo(
                $cart,
            );
        }

        $now ??=
            CarbonImmutable::now();

        $validation =
            $this
                ->validationService
                ->validate(
                    $promoCode,
                    new PromoValidationContext(
                        subtotal: $cart->subtotal,

                        userId: $userId,

                        now: $now,
                    ),
                );

        if (! $validation->eligible) {
            $this->promoSession
                ->forget();

            return $this->withoutPromo(
                $cart,
            );
        }

        $resolved =
            $this->resolver
                ->resolve(
                    $promoCode,
                );

        $discount =
            $this
                ->calculator
                ->calculate(
                    promo: $resolved,

                    subtotal: $cart->subtotal,
                );

        return new CartPricingSummaryData(
            cart: $cart,

            promo: new CartPromoData(
                promoCodeId: $promoCode->id,

                code: $promoCode->code,

                discountAmount: $discount
                    ->discountAmount,
            ),

            discountTotal: $discount
                ->discountAmount,

            grandTotal: $discount
                ->remainingSubtotal,
        );
    }

    private function withoutPromo(
        CartSummaryData $cart,
    ): CartPricingSummaryData {
        return new CartPricingSummaryData(
            cart: $cart,

            promo: null,

            discountTotal: 0,

            grandTotal: $cart->subtotal,
        );
    }
}
