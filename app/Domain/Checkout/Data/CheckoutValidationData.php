<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Data;

use App\Domain\Cart\Data\CartPromoData;
use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Shipping\Data\ShippingRateQuote;
use App\Models\Address;

final readonly class CheckoutValidationData
{
    public function __construct(
        public CartSummaryData $cart,
        public CheckoutTotalsData $totals,
        public Address $address,
        public PaymentMethod $paymentMethod,
        public ShippingRateQuote $shippingQuote,
        public ?CartPromoData $promo = null,
    ) {}
}
