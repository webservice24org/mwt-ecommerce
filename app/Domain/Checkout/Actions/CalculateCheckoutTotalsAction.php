<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

use App\Domain\Cart\Data\CartPricingSummaryData;
use App\Domain\Checkout\Data\CheckoutTotalsData;
use Illuminate\Validation\ValidationException;

final class CalculateCheckoutTotalsAction
{
    public function execute(
        CartPricingSummaryData $pricing,
        int $deliveryTotal,
    ): CheckoutTotalsData {
        if ($deliveryTotal < 0) {
            throw ValidationException::withMessages([
                'delivery_total' => 'Delivery total cannot be negative.',
            ]);
        }

        /*
         * All cart pricing is already authoritative.
         *
         * Promo calculations must NEVER be duplicated
         * inside checkout.
         */
        $subtotal =
            $pricing
                ->cart
                ->subtotal;

        $discountTotal =
            $pricing
                ->discountTotal;

        /*
         * Tax is currently zero in the existing
         * checkout contract.
         */
        $taxTotal = 0;

        /*
         * CartPricingSummaryData::grandTotal is:
         *
         * subtotal - promo discount
         *
         * Checkout adds delivery and tax.
         */
        $grandTotal =
            $pricing->grandTotal
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
