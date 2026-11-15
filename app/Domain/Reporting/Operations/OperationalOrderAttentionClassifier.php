<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\OperationalOrderAttentionData;
use App\Domain\Reporting\Enums\OperationalOrderAttentionState;
use Carbon\CarbonImmutable;

final class OperationalOrderAttentionClassifier
{
    public function classify(
        string $status,
        string $paymentStatus,
        string $fulfilmentStatus,
        CarbonImmutable $placedAt,
        ?CarbonImmutable $paymentDueAt,
        CarbonImmutable $asOf,
    ): OperationalOrderAttentionData {
        $state =
            $this->resolveState(
                status: $status,

                paymentStatus: $paymentStatus,

                fulfilmentStatus: $fulfilmentStatus,

                placedAt: $placedAt,

                paymentDueAt: $paymentDueAt,

                asOf: $asOf,
            );

        return new OperationalOrderAttentionData(
            state: $state,
        );
    }

    private function resolveState(
        string $status,
        string $paymentStatus,
        string $fulfilmentStatus,
        CarbonImmutable $placedAt,
        ?CarbonImmutable $paymentDueAt,
        CarbonImmutable $asOf,
    ): OperationalOrderAttentionState {
        /*
         * Highest priority:
         * an explicitly overdue payment.
         */
        if (
            $paymentStatus !==
            PaymentStatus::Paid->value &&
            $paymentDueAt !== null &&
            $paymentDueAt->lessThan(
                $asOf,
            )
        ) {
            return OperationalOrderAttentionState::PaymentOverdue;
        }

        /*
         * Returned orders should surface prominently
         * for operational follow-up.
         */
        if (
            $fulfilmentStatus ===
            FulfilmentStatus::Returned->value
        ) {
            return OperationalOrderAttentionState::ReturnedAttention;
        }

        /*
         * Delivered but not fully paid.
         */
        if (
            $fulfilmentStatus ===
            FulfilmentStatus::Delivered->value &&
            $paymentStatus !==
            PaymentStatus::Paid->value
        ) {
            return OperationalOrderAttentionState::DeliveredPaymentDue;
        }

        $ageHours =
            max(
                0,
                $placedAt
                    ->diffInHours(
                        $asOf,
                    ),
            );

        if (
            $status ===
            OrderStatus::Pending->value &&
            $ageHours >=
            $this->pendingAgeHours()
        ) {
            return OperationalOrderAttentionState::PendingTooLong;
        }

        if (
            $status ===
            OrderStatus::Processing->value &&
            $ageHours >=
            $this->processingAgeHours()
        ) {
            return OperationalOrderAttentionState::ProcessingTooLong;
        }

        if (
            $fulfilmentStatus ===
            FulfilmentStatus::Unfulfilled->value &&
            $ageHours >=
            $this->unfulfilledAgeHours()
        ) {
            return OperationalOrderAttentionState::FulfilmentStale;
        }

        return OperationalOrderAttentionState::None;
    }

    private function pendingAgeHours(): int
    {
        return max(
            1,
            (int) config(
                'reporting.operations.pending_age_hours',
                24,
            ),
        );
    }

    private function processingAgeHours(): int
    {
        return max(
            1,
            (int) config(
                'reporting.operations.processing_age_hours',
                48,
            ),
        );
    }

    private function unfulfilledAgeHours(): int
    {
        return max(
            1,
            (int) config(
                'reporting.operations.unfulfilled_age_hours',
                48,
            ),
        );
    }
}
