<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Promotion;

use App\Domain\Promotion\Data\ResolvedPromoCode;
use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Services\PromoEligibilityService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PromoEligibilityServiceTest extends TestCase
{
    public function test_active_promo_is_eligible(): void
    {
        $promo = $this->promo();

        $result = (new PromoEligibilityService)
            ->check(
                promo: $promo,
                subtotal: 1000,
                now: CarbonImmutable::parse(
                    '2026-11-10 12:00:00',
                ),
            );

        $this->assertTrue(
            $result->eligible,
        );

        $this->assertNull(
            $result->reason,
        );
    }

    public function test_inactive_promo_is_rejected(): void
    {
        $promo = $this->promo(
            isActive: false,
        );

        $result = (new PromoEligibilityService)
            ->check(
                promo: $promo,
                subtotal: 1000,
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'This promo code is inactive.',
            $result->reason,
        );
    }

    public function test_future_promo_is_rejected(): void
    {
        $promo = $this->promo(
            startsAt: CarbonImmutable::parse(
                '2026-12-01 00:00:00',
            ),
        );

        $result = (new PromoEligibilityService)
            ->check(
                promo: $promo,
                subtotal: 1000,
                now: CarbonImmutable::parse(
                    '2026-11-10 12:00:00',
                ),
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'This promo code is not active yet.',
            $result->reason,
        );
    }

    public function test_expired_promo_is_rejected(): void
    {
        $promo = $this->promo(
            endsAt: CarbonImmutable::parse(
                '2026-11-01 23:59:59',
            ),
        );

        $result = (new PromoEligibilityService)
            ->check(
                promo: $promo,
                subtotal: 1000,
                now: CarbonImmutable::parse(
                    '2026-11-10 12:00:00',
                ),
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'This promo code has expired.',
            $result->reason,
        );
    }

    public function test_minimum_subtotal_is_enforced(): void
    {
        $promo = $this->promo(
            minimumSubtotal: 2000,
        );

        $result = (new PromoEligibilityService)
            ->check(
                promo: $promo,
                subtotal: 1999,
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'The minimum order amount for this promo code has not been reached.',
            $result->reason,
        );
    }

    public function test_exact_minimum_subtotal_is_eligible(): void
    {
        $promo = $this->promo(
            minimumSubtotal: 2000,
        );

        $result = (new PromoEligibilityService)
            ->check(
                promo: $promo,
                subtotal: 2000,
            );

        $this->assertTrue(
            $result->eligible,
        );
    }

    private function promo(
        bool $isActive = true,
        ?int $minimumSubtotal = null,
        ?CarbonImmutable $startsAt = null,
        ?CarbonImmutable $endsAt = null,
    ): ResolvedPromoCode {
        return new ResolvedPromoCode(
            promoCodeId: 1,
            code: 'SAVE20',
            discountType: DiscountType::Percentage,
            discountValue: 2000,
            maximumDiscount: null,
            minimumSubtotal: $minimumSubtotal,
            startsAt: $startsAt,
            endsAt: $endsAt,
            isActive: $isActive,
            isStackable: false,
        );
    }
}
