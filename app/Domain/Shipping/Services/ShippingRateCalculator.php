<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Data\ShippingRateQuote;
use App\Models\ShippingRate;
use Illuminate\Validation\ValidationException;

final readonly class ShippingRateCalculator
{
    public function quote(
        ShippingRate $rate,
        int $orderSubtotal,
    ): ShippingRateQuote {
        if ($orderSubtotal < 0) {
            throw ValidationException::withMessages([
                'subtotal' => 'Order subtotal cannot be negative.',
            ]);
        }

        if (! $rate->is_active) {
            throw ValidationException::withMessages([
                'shipping_rate' => 'The shipping rate is not active.',
            ]);
        }

        $method = $rate->shippingMethod;

        if (! $method->is_active) {
            throw ValidationException::withMessages([
                'shipping_method' => 'The shipping method is not active.',
            ]);
        }

        if (
            $rate->min_order_amount !== null
            && $orderSubtotal < $rate->min_order_amount
        ) {
            throw ValidationException::withMessages([
                'subtotal' => 'The order does not meet the minimum amount for this shipping rate.',
            ]);
        }

        if (
            $rate->max_order_amount !== null
            && $orderSubtotal > $rate->max_order_amount
        ) {
            throw ValidationException::withMessages([
                'subtotal' => 'The order exceeds the maximum amount for this shipping rate.',
            ]);
        }

        $free =
            $rate->free_shipping_threshold !== null
            && $orderSubtotal >= $rate->free_shipping_threshold;

        return new ShippingRateQuote(
            shippingMethodId: $method->id,
            shippingRateId: $rate->id,
            methodCode: $method->code,
            methodName: $method->name,
            rateCode: $rate->code,
            rateName: $rate->name,
            amount: $free
            ? 0
            : $rate->amount,
            free: $free,
        );
    }
}
