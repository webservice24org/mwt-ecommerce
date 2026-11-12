<?php

declare(strict_types=1);

namespace Tests\Unit\Checkout;

use App\Domain\Cart\Data\CartPricingSummaryData;
use App\Domain\Cart\Data\CartPromoData;
use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Checkout\Actions\CalculateCheckoutTotalsAction;
use PHPUnit\Framework\TestCase;

final class CalculateCheckoutTotalsActionTest extends TestCase
{
    public function test_checkout_totals_are_calculated_from_authoritative_cart_pricing(): void
    {
        $pricing =
            $this->pricing(
                subtotal: 25000,

                discount: 0,
            );

        $totals =
            (
                new CalculateCheckoutTotalsAction
            )->execute(
                pricing: $pricing,

                deliveryTotal: 0,
            );

        $this->assertSame(
            25000,
            $totals->subtotal,
        );

        $this->assertSame(
            0,
            $totals->discountTotal,
        );

        $this->assertSame(
            0,
            $totals->deliveryTotal,
        );

        $this->assertSame(
            0,
            $totals->taxTotal,
        );

        $this->assertSame(
            25000,
            $totals->grandTotal,
        );
    }

    public function test_promo_discount_is_preserved_in_checkout_totals(): void
    {
        $pricing =
            $this->pricing(
                subtotal: 550000,

                discount: 15000,
            );

        $totals =
            (
                new CalculateCheckoutTotalsAction
            )->execute(
                pricing: $pricing,

                deliveryTotal: 0,
            );

        $this->assertSame(
            550000,
            $totals->subtotal,
        );

        $this->assertSame(
            15000,
            $totals->discountTotal,
        );

        $this->assertSame(
            535000,
            $totals->grandTotal,
        );
    }

    public function test_delivery_is_added_after_promo_discount(): void
    {
        $pricing =
            $this->pricing(
                subtotal: 550000,

                discount: 15000,
            );

        $totals =
            (
                new CalculateCheckoutTotalsAction
            )->execute(
                pricing: $pricing,

                deliveryTotal: 12000,
            );

        $this->assertSame(
            550000,
            $totals->subtotal,
        );

        $this->assertSame(
            15000,
            $totals->discountTotal,
        );

        $this->assertSame(
            12000,
            $totals->deliveryTotal,
        );

        $this->assertSame(
            547000,
            $totals->grandTotal,
        );
    }

    public function test_totals_array_uses_checkout_contract_keys(): void
    {
        $pricing =
            $this->pricing(
                subtotal: 9999,

                discount: 999,
            );

        $totals =
            (
                new CalculateCheckoutTotalsAction
            )->execute(
                pricing: $pricing,

                deliveryTotal: 500,
            );

        $this->assertSame(
            [
                'subtotal' => 9999,

                'discount_total' => 999,

                'delivery_total' => 500,

                'tax_total' => 0,

                'grand_total' => 9500,
            ],
            $totals->toArray(),
        );
    }

    private function pricing(
        int $subtotal,
        int $discount,
    ): CartPricingSummaryData {
        return new CartPricingSummaryData(
            cart: new CartSummaryData(
                items: [],

                itemCount: 1,

                subtotal: $subtotal,
            ),

            promo: $discount > 0
            ? new CartPromoData(
                promoCodeId: 1,

                code: 'TEST10',

                discountAmount: $discount,
            )
            : null,

            discountTotal: $discount,

            grandTotal: $subtotal -
            $discount,
        );
    }
}
