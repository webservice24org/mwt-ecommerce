<?php

declare(strict_types=1);

namespace Tests\Unit\Checkout;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Checkout\Data\CheckoutTotalsData;
use App\Domain\Checkout\Data\CheckoutValidationData;
use App\Domain\Orders\Enums\PaymentMethod;
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

        $checkout = new CheckoutValidationData(
            cart: $cart,
            totals: $totals,
            address: $address,
            paymentMethod: $paymentMethod,
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

        $checkout = new CheckoutValidationData(
            cart: $cart,
            totals: $totals,
            address: new Address,
            paymentMethod: PaymentMethod::CashOnDelivery,
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
    }
}
