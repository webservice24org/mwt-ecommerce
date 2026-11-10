<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Promotion;

use App\Domain\Promotion\Data\ResolvedPromoCode;
use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Services\PromoDiscountCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PromoDiscountCalculatorTest extends TestCase
{
    public function test_fixed_discount_is_calculated(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Fixed,
            discountValue: 250,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1000,
            );

        $this->assertSame(
            250,
            $result->requestedDiscount,
        );

        $this->assertSame(
            250,
            $result->discountAmount,
        );

        $this->assertSame(
            750,
            $result->remainingSubtotal,
        );

        $this->assertFalse(
            $result->cappedByMaximumDiscount,
        );

        $this->assertFalse(
            $result->cappedBySubtotal,
        );
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Fixed,
            discountValue: 1500,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1000,
            );

        $this->assertSame(
            1500,
            $result->requestedDiscount,
        );

        $this->assertSame(
            1000,
            $result->discountAmount,
        );

        $this->assertSame(
            0,
            $result->remainingSubtotal,
        );

        $this->assertTrue(
            $result->cappedBySubtotal,
        );
    }

    public function test_percentage_discount_is_calculated_using_basis_points(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Percentage,
            discountValue: 2000,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1000,
            );

        $this->assertSame(
            200,
            $result->requestedDiscount,
        );

        $this->assertSame(
            200,
            $result->discountAmount,
        );

        $this->assertSame(
            800,
            $result->remainingSubtotal,
        );
    }

    public function test_percentage_discount_uses_integer_money_arithmetic(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Percentage,
            discountValue: 1500,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1250,
            );

        $this->assertSame(
            187,
            $result->discountAmount,
        );

        $this->assertSame(
            1063,
            $result->remainingSubtotal,
        );
    }

    public function test_percentage_discount_respects_maximum_discount(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Percentage,
            discountValue: 2500,
            maximumDiscount: 300,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 2000,
            );

        $this->assertSame(
            500,
            $result->requestedDiscount,
        );

        $this->assertSame(
            300,
            $result->discountAmount,
        );

        $this->assertSame(
            1700,
            $result->remainingSubtotal,
        );

        $this->assertTrue(
            $result->cappedByMaximumDiscount,
        );

        $this->assertFalse(
            $result->cappedBySubtotal,
        );
    }

    public function test_fixed_discount_can_also_respect_maximum_discount(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Fixed,
            discountValue: 500,
            maximumDiscount: 300,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1000,
            );

        $this->assertSame(
            500,
            $result->requestedDiscount,
        );

        $this->assertSame(
            300,
            $result->discountAmount,
        );

        $this->assertSame(
            700,
            $result->remainingSubtotal,
        );

        $this->assertTrue(
            $result->cappedByMaximumDiscount,
        );
    }

    public function test_zero_subtotal_returns_zero_discount(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Fixed,
            discountValue: 500,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 0,
            );

        $this->assertSame(
            0,
            $result->discountAmount,
        );

        $this->assertSame(
            0,
            $result->remainingSubtotal,
        );
    }

    public function test_one_hundred_percent_discount_can_reduce_subtotal_to_zero(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Percentage,
            discountValue: 10_000,
        );

        $result = (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 2500,
            );

        $this->assertSame(
            2500,
            $result->discountAmount,
        );

        $this->assertSame(
            0,
            $result->remainingSubtotal,
        );
    }

    public function test_negative_subtotal_is_rejected(): void
    {
        $promo = $this->promo();

        $this->expectException(
            InvalidArgumentException::class,
        );

        (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: -1,
            );
    }

    public function test_percentage_above_one_hundred_percent_is_rejected(): void
    {
        $promo = $this->promo(
            discountType: DiscountType::Percentage,
            discountValue: 10_001,
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1000,
            );
    }

    public function test_negative_discount_value_is_rejected(): void
    {
        $promo = $this->promo(
            discountValue: -1,
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1000,
            );
    }

    public function test_negative_maximum_discount_is_rejected(): void
    {
        $promo = $this->promo(
            maximumDiscount: -1,
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        (new PromoDiscountCalculator)
            ->calculate(
                promo: $promo,
                subtotal: 1000,
            );
    }

    private function promo(
        DiscountType $discountType =
        DiscountType::Percentage,
        int $discountValue = 2000,
        ?int $maximumDiscount = null,
    ): ResolvedPromoCode {
        return new ResolvedPromoCode(
            promoCodeId: 1,
            code: 'SAVE20',
            discountType: $discountType,
            discountValue: $discountValue,
            maximumDiscount: $maximumDiscount,
            minimumSubtotal: null,
            startsAt: null,
            endsAt: null,
            isActive: true,
            isStackable: false,
        );
    }
}
