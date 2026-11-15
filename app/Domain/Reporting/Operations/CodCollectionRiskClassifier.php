<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\CodCollectionRiskData;
use App\Domain\Reporting\Enums\CodCollectionRiskState;
use Carbon\CarbonImmutable;

final class CodCollectionRiskClassifier
{
    public function classify(
        string $paymentStatus,
        string $fulfilmentStatus,
        CarbonImmutable $placedAt,
        ?CarbonImmutable $paymentDueAt,
        CarbonImmutable $asOf,
    ): CodCollectionRiskData {
        return new CodCollectionRiskData(
            state: $this->resolveState(
                paymentStatus: $paymentStatus,

                fulfilmentStatus: $fulfilmentStatus,

                placedAt: $placedAt,

                paymentDueAt: $paymentDueAt,

                asOf: $asOf,
            ),
        );
    }

    private function resolveState(
        string $paymentStatus,
        string $fulfilmentStatus,
        CarbonImmutable $placedAt,
        ?CarbonImmutable $paymentDueAt,
        CarbonImmutable $asOf,
    ): CodCollectionRiskState {
        if (
            $paymentStatus ===
            PaymentStatus::Paid->value
        ) {
            return CodCollectionRiskState::None;
        }

        $paymentOverdue =
            $paymentDueAt !== null &&
            $paymentDueAt
                ->addHours(
                    $this->dueGraceHours(),
                )
                ->lessThan(
                    $asOf,
                );

        if (
            $fulfilmentStatus ===
            FulfilmentStatus::Delivered->value
        ) {
            if (
                $paymentOverdue
            ) {
                return CodCollectionRiskState::DeliveredOverdue;
            }

            if (
                $paymentStatus ===
                PaymentStatus::Due->value
            ) {
                return CodCollectionRiskState::DeliveredDue;
            }

            if (
                $paymentStatus ===
                PaymentStatus::Unpaid->value
            ) {
                return CodCollectionRiskState::DeliveredUnpaid;
            }
        }

        if (
            $paymentOverdue
        ) {
            return CodCollectionRiskState::DueOverdue;
        }

        if (
            $paymentStatus ===
            PaymentStatus::Due->value
        ) {
            return CodCollectionRiskState::DueRecent;
        }

        $ageHours =
            max(
                0,
                (int) floor(
                    $placedAt
                        ->diffInHours(
                            $asOf,
                        ),
                ),
            );

        if (
            $paymentStatus ===
            PaymentStatus::Unpaid->value &&
            $ageHours >=
            $this->unpaidStaleHours()
        ) {
            return CodCollectionRiskState::UnpaidStale;
        }

        if (
            $paymentStatus ===
            PaymentStatus::Unpaid->value
        ) {
            return CodCollectionRiskState::UnpaidRecent;
        }

        return CodCollectionRiskState::None;
    }

    private function unpaidStaleHours(): int
    {
        return max(
            1,
            (int) config(
                'reporting.cod_operations.unpaid_stale_hours',
                48,
            ),
        );
    }

    private function dueGraceHours(): int
    {
        return max(
            0,
            (int) config(
                'reporting.cod_operations.due_grace_hours',
                24,
            ),
        );
    }
}
