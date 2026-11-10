<?php

declare(strict_types=1);

namespace Tests\Unit\Checkout;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Checkout\Data\CheckoutTotalsData;
use App\Domain\Checkout\Data\CheckoutValidationData;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Shipping\Data\ShippingRateQuote;
use App\Models\Address;
use PHPUnit\Framework\TestCase;

final class CheckoutValidationDataTest extends TestCase
{
    public function test_it_preserves_validated_checkout_state(): void
    {
        $cart = new CartSummaryData(
            items: [],
            itemCount: 2,
            subtotal: 25000.0,
        );

        $totals = new CheckoutTotalsData(
            subtotal: 25000,
            discountTotal: 0,
            deliveryTotal: 0,
            taxTotal: 0,
            grandTotal: 25000,
        );

        $address = new Address;

        $paymentMethod =
            PaymentMethod::CashOnDelivery;

        $shippingQuote =
            $this->shippingQuote(
                amount: 0,
                free: true,
            );

        $checkout = new CheckoutValidationData(
            cart: $cart,
            totals: $totals,
            address: $address,
            paymentMethod: $paymentMethod,
            shippingQuote: $shippingQuote,
        );

        $this->assertSame(
            $cart,
            $checkout->cart,
        );

        $this->assertSame(
            $totals,
            $checkout->totals,
        );

        $this->assertSame(
            $address,
            $checkout->address,
        );

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $checkout->paymentMethod,
        );

        $this->assertSame(
            $shippingQuote,
            $checkout->shippingQuote,
        );
    }

    public function test_it_preserves_authoritative_monetary_values(): void
    {
        $cart = new CartSummaryData(
            items: [],
            itemCount: 1,
            subtotal: 34999.0,
        );

        $totals = new CheckoutTotalsData(
            subtotal: 34999,
            discountTotal: 1000,
            deliveryTotal: 150,
            taxTotal: 0,
            grandTotal: 34149,
        );

        $shippingQuote =
            $this->shippingQuote(
                amount: 150,
                free: false,
            );

        $checkout = new CheckoutValidationData(
            cart: $cart,
            totals: $totals,
            address: new Address,
            paymentMethod: PaymentMethod::CashOnDelivery,
            shippingQuote: $shippingQuote,
        );

        $this->assertSame(
            34999,
            $checkout->totals->subtotal,
        );

        $this->assertSame(
            1000,
            $checkout
                ->totals
                ->discountTotal,
        );

        $this->assertSame(
            150,
            $checkout
                ->totals
                ->deliveryTotal,
        );

        $this->assertSame(
            0,
            $checkout
                ->totals
                ->taxTotal,
        );

        $this->assertSame(
            34149,
            $checkout
                ->totals
                ->grandTotal,
        );

        $this->assertSame(
            150,
            $checkout
                ->shippingQuote
                ->amount,
        );

        $this->assertFalse(
            $checkout
                ->shippingQuote
                ->free,
        );
    }

    private function shippingQuote(
        int $amount,
        bool $free,
    ): ShippingRateQuote {
        return new ShippingRateQuote(
            shippingMethodId: 1,
            shippingRateId: 1,
            methodCode: 'standard',
            methodName: 'Standard Delivery',
            rateCode: 'standard-rate',
            rateName: 'Standard Rate',
            amount: $amount,
            free: $free,
        );
    }
}
