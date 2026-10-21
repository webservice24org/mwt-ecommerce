<?php

declare(strict_types=1);

namespace Tests\Unit\Checkout;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Checkout\Actions\CalculateCheckoutTotalsAction;
use PHPUnit\Framework\TestCase;

final class CalculateCheckoutTotalsActionTest extends TestCase
{
    public function test_checkout_totals_are_calculated_from_cart_subtotal(): void
    {
        $cart = new CartSummaryData(
            items: [],
            itemCount: 2,
            subtotal: 25000,
        );

        $totals = (
            new CalculateCheckoutTotalsAction
        )->execute(
            $cart,
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

    public function test_totals_array_uses_checkout_contract_keys(): void
    {
        $cart = new CartSummaryData(
            items: [],
            itemCount: 1,
            subtotal: 9999,
        );

        $totals = (
            new CalculateCheckoutTotalsAction
        )->execute(
            $cart,
        );

        $this->assertSame(
            [
                'subtotal' => 9999,
                'discount_total' => 0,
                'delivery_total' => 0,
                'tax_total' => 0,
                'grand_total' => 9999,
            ],
            $totals->toArray(),
        );
    }
}
