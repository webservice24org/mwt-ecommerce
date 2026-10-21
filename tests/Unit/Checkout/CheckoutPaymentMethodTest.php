<?php

declare(strict_types=1);

namespace Tests\Unit\Checkout;

use App\Domain\Orders\Enums\PaymentMethod;
use PHPUnit\Framework\TestCase;

final class CheckoutPaymentMethodTest extends TestCase
{
    public function test_cash_on_delivery_has_expected_value(): void
    {
        $this->assertSame(
            'cash_on_delivery',
            PaymentMethod::CashOnDelivery->value,
        );
    }

    public function test_cash_on_delivery_has_customer_facing_label(): void
    {
        $method =
            PaymentMethod::CashOnDelivery;

        $this->assertSame(
            'Cash on Delivery',
            $method->label(),
        );

        $this->assertSame(
            'Pay when your order is delivered.',
            $method->description(),
        );
    }

    public function test_unsupported_payment_method_is_not_valid(): void
    {
        $this->assertNull(
            PaymentMethod::tryFrom(
                'credit_card',
            ),
        );

        $this->assertNull(
            PaymentMethod::tryFrom(
                'stripe',
            ),
        );

        $this->assertNull(
            PaymentMethod::tryFrom(
                'bkash',
            ),
        );
    }
}
