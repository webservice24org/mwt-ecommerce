<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Checkout\Data\CheckoutTotalsData;
use Illuminate\Validation\ValidationException;

final class CalculateCheckoutTotalsAction
{
    public function execute(
        CartSummaryData $cart,
        int $deliveryTotal,
    ): CheckoutTotalsData {
        if ($deliveryTotal < 0) {
            throw ValidationException::withMessages([
                'delivery_total' => 'Delivery total cannot be negative.',
            ]);
        }

        $subtotal = (int) round(
            $cart->subtotal,
        );

        $discountTotal = 0;
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
