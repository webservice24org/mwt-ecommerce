<?php

declare(strict_types=1);

namespace Tests\Unit\Orders;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

final class OrderEnumsTest extends TestCase
{
    public function test_order_status_values_are_stable(): void
    {
        $this->assertSame(
            'pending',
            OrderStatus::Pending->value,
        );

        $this->assertSame(
            'confirmed',
            OrderStatus::Confirmed->value,
        );

        $this->assertSame(
            'processing',
            OrderStatus::Processing->value,
        );

        $this->assertSame(
            'completed',
            OrderStatus::Completed->value,
        );

        $this->assertSame(
            'cancelled',
            OrderStatus::Cancelled->value,
        );
    }

    public function test_payment_method_values_are_stable(): void
    {
        $this->assertSame(
            'cash_on_delivery',
            PaymentMethod::CashOnDelivery->value,
        );
    }

    public function test_payment_status_values_are_stable(): void
    {
        $this->assertSame(
            'unpaid',
            PaymentStatus::Unpaid->value,
        );

        $this->assertSame(
            'pending',
            PaymentStatus::Pending->value,
        );

        $this->assertSame(
            'paid',
            PaymentStatus::Paid->value,
        );

        $this->assertSame(
            'failed',
            PaymentStatus::Failed->value,
        );

        $this->assertSame(
            'partially_refunded',
            PaymentStatus::PartiallyRefunded->value,
        );

        $this->assertSame(
            'refunded',
            PaymentStatus::Refunded->value,
        );
    }

    public function test_fulfilment_status_values_are_stable(): void
    {
        $this->assertSame(
            'unfulfilled',
            FulfilmentStatus::Unfulfilled->value,
        );

        $this->assertSame(
            'processing',
            FulfilmentStatus::Processing->value,
        );

        $this->assertSame(
            'shipped',
            FulfilmentStatus::Shipped->value,
        );

        $this->assertSame(
            'delivered',
            FulfilmentStatus::Delivered->value,
        );

        $this->assertSame(
            'returned',
            FulfilmentStatus::Returned->value,
        );
    }

    public function test_unknown_values_are_rejected(): void
    {
        $this->assertNull(
            OrderStatus::tryFrom(
                'unknown',
            ),
        );

        $this->assertNull(
            PaymentMethod::tryFrom(
                'unknown_gateway',
            ),
        );

        $this->assertNull(
            PaymentStatus::tryFrom(
                'unknown',
            ),
        );

        $this->assertNull(
            FulfilmentStatus::tryFrom(
                'unknown',
            ),
        );
    }
}
