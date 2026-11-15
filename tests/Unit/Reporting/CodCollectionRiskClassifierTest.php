<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Enums\CodCollectionRiskState;
use App\Domain\Reporting\Operations\CodCollectionRiskClassifier;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class CodCollectionRiskClassifierTest extends TestCase
{
    public function test_paid_order_has_no_collection_risk(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Paid->value,

                    fulfilmentStatus: FulfilmentStatus::Delivered->value,

                    placedAt: $this->now()
                        ->subDays(
                            5,
                        ),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::None,
            $result->state,
        );
    }

    public function test_recent_unpaid_order_is_classified_as_recent(): void
    {
        config([
            'reporting.cod_operations.unpaid_stale_hours' => 48,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Unpaid->value,

                    fulfilmentStatus: FulfilmentStatus::Unfulfilled->value,

                    placedAt: $this->now()
                        ->subHours(
                            12,
                        ),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::UnpaidRecent,
            $result->state,
        );
    }

    public function test_old_unpaid_order_is_stale(): void
    {
        config([
            'reporting.cod_operations.unpaid_stale_hours' => 48,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Unpaid->value,

                    fulfilmentStatus: FulfilmentStatus::Unfulfilled->value,

                    placedAt: $this->now()
                        ->subHours(
                            72,
                        ),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::UnpaidStale,
            $result->state,
        );
    }

    public function test_due_order_is_due_recent_when_not_overdue(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Due->value,

                    fulfilmentStatus: FulfilmentStatus::Processing->value,

                    placedAt: $this->now()
                        ->subDay(),

                    paymentDueAt: $this->now()
                        ->subHours(
                            2,
                        ),

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::DueRecent,
            $result->state,
        );
    }

    public function test_due_order_becomes_overdue_after_grace_period(): void
    {
        config([
            'reporting.cod_operations.due_grace_hours' => 24,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Due->value,

                    fulfilmentStatus: FulfilmentStatus::Processing->value,

                    placedAt: $this->now()
                        ->subDays(
                            3,
                        ),

                    paymentDueAt: $this->now()
                        ->subHours(
                            30,
                        ),

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::DueOverdue,
            $result->state,
        );
    }

    public function test_delivered_unpaid_is_high_risk(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Unpaid->value,

                    fulfilmentStatus: FulfilmentStatus::Delivered->value,

                    placedAt: $this->now()
                        ->subDay(),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::DeliveredUnpaid,
            $result->state,
        );
    }

    public function test_delivered_due_is_critical(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Due->value,

                    fulfilmentStatus: FulfilmentStatus::Delivered->value,

                    placedAt: $this->now()
                        ->subDay(),

                    paymentDueAt: null,

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::DeliveredDue,
            $result->state,
        );

        $this->assertSame(
            'critical',
            $result
                ->state
                ->severity(),
        );
    }

    public function test_delivered_overdue_has_highest_collection_priority(): void
    {
        config([
            'reporting.cod_operations.due_grace_hours' => 24,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    paymentStatus: PaymentStatus::Due->value,

                    fulfilmentStatus: FulfilmentStatus::Delivered->value,

                    placedAt: $this->now()
                        ->subDays(
                            4,
                        ),

                    paymentDueAt: $this->now()
                        ->subDays(
                            2,
                        ),

                    asOf: $this->now(),
                );

        $this->assertSame(
            CodCollectionRiskState::DeliveredOverdue,
            $result->state,
        );

        $this->assertSame(
            70,
            $result
                ->state
                ->priority(),
        );
    }

    private function classifier(): CodCollectionRiskClassifier
    {
        return new CodCollectionRiskClassifier;
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            '2026-11-15 12:00:00',
            'Asia/Dhaka',
        );
    }
}
