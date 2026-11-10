<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Services;

use App\Domain\Promotion\Data\PromoValidationContext;
use App\Domain\Promotion\Results\PromoValidationResult;
use App\Models\PromoCode;

final class PromoValidationService
{
    public function __construct(
        private readonly PromoCodeResolver $resolver,
        private readonly PromoEligibilityService $eligibilityService,
    ) {}

    public function validate(
        PromoCode $promoCode,
        PromoValidationContext $context,
    ): PromoValidationResult {
        $resolved = $this->resolver->resolve(
            $promoCode,
        );

        $eligibility = $this->eligibilityService->check(
            promo: $resolved,
            subtotal: $context->subtotal,
            now: $context->now,
        );

        if (! $eligibility->eligible) {
            return PromoValidationResult::rejected(
                $eligibility->reason
                ?? 'This promo code is not eligible.',
            );
        }

        if (
            $promoCode->usage_limit !== null &&
            $promoCode->redeemed_count >=
            $promoCode->usage_limit
        ) {
            return PromoValidationResult::rejected(
                'This promo code has reached its usage limit.',
            );
        }

        if (
            $context->userId !== null &&
            $promoCode->usage_limit_per_customer !== null
        ) {
            $customerUsageCount = $promoCode
                ->usages()
                ->where(
                    'user_id',
                    $context->userId,
                )
                ->count();

            if (
                $customerUsageCount >=
                $promoCode->usage_limit_per_customer
            ) {
                return PromoValidationResult::rejected(
                    'You have already used this promo code the maximum number of times.',
                );
            }
        }

        return PromoValidationResult::eligible();
    }
}
