<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Checkout\Data\CheckoutTotalsData;

final class CalculateCheckoutTotalsAction
{
    public function execute(
        CartSummaryData $cart,
    ): CheckoutTotalsData {
        $subtotal = (int) round(
            $cart->subtotal,
        );

        /*
         * Phase 6.3C foundation.
         *
         * These values are intentionally calculated on the
         * server and are not accepted from the browser.
         */
        $discountTotal = 0;
        $deliveryTotal = 0;
        $taxTotal = 0;

        $grandTotal =
            $subtotal
            - $discountTotal
            + $deliveryTotal
            + $taxTotal;

        return new CheckoutTotalsData(
            subtotal: $subtotal,
            discountTotal: $discountTotal,
            deliveryTotal: $deliveryTotal,
            taxTotal: $taxTotal,
            grandTotal: $grandTotal,
        );
    }
}
