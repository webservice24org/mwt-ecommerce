<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Enums\OperationalOrderAttentionState;
use App\Domain\Reporting\Operations\OperationalOrderAttentionClassifier;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class OperationalOrderAttentionClassifierTest extends TestCase
{
    public function test_fresh_pending_order_requires_no_attention(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    status: OrderStatus::Pending->value,

                    paymentStatus: PaymentStatus::Unpaid->value,

                    fulfilmentStatus: FulfilmentStatus::Unfulfilled->value,

                    placedAt: $this->now()
                        ->subHours(
                            2,
                        ),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            OperationalOrderAttentionState::None,
            $result->state,
        );
    }

    public function test_old_pending_order_is_flagged(): void
    {
        config([
            'reporting.operations.pending_age_hours' => 24,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    status: OrderStatus::Pending->value,

                    paymentStatus: PaymentStatus::Unpaid->value,

                    fulfilmentStatus: FulfilmentStatus::Unfulfilled->value,

                    placedAt: $this->now()
                        ->subHours(
                            25,
                        ),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            OperationalOrderAttentionState::PendingTooLong,
            $result->state,
        );
    }

    public function test_processing_order_can_become_stale(): void
    {
        config([
            'reporting.operations.processing_age_hours' => 48,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    status: OrderStatus::Processing->value,

                    paymentStatus: PaymentStatus::Paid->value,

                    fulfilmentStatus: FulfilmentStatus::Processing->value,

                    placedAt: $this->now()
                        ->subHours(
                            50,
                        ),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            OperationalOrderAttentionState::ProcessingTooLong,
            $result->state,
        );
    }

    public function test_delivered_unpaid_order_is_flagged(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    status: OrderStatus::Completed->value,

                    paymentStatus: PaymentStatus::Due->value,

                    fulfilmentStatus: FulfilmentStatus::Delivered->value,

                    placedAt: $this->now()
                        ->subDay(),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            OperationalOrderAttentionState::DeliveredPaymentDue,
            $result->state,
        );
    }

    public function test_explicit_overdue_payment_has_highest_priority(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    status: OrderStatus::Completed->value,

                    paymentStatus: PaymentStatus::Due->value,

                    fulfilmentStatus: FulfilmentStatus::Delivered->value,

                    placedAt: $this->now()
                        ->subDays(
                            5,
                        ),

                    paymentDueAt: $this->now()
                        ->subDay(),

                    asOf: $this->now(),
                );

        $this->assertSame(
            OperationalOrderAttentionState::PaymentOverdue,
            $result->state,
        );

        $this->assertSame(
            'critical',
            $result
                ->state
                ->severity(),
        );
    }

    public function test_returned_order_requires_attention(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    status: OrderStatus::Processing->value,

                    paymentStatus: PaymentStatus::Paid->value,

                    fulfilmentStatus: FulfilmentStatus::Returned->value,

                    placedAt: $this->now()
                        ->subDay(),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            OperationalOrderAttentionState::ReturnedAttention,
            $result->state,
        );
    }

    private function classifier(): OperationalOrderAttentionClassifier
    {
        return new OperationalOrderAttentionClassifier;
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            '2026-11-15 12:00:00',
            'Asia/Dhaka',
        );
    }
}
