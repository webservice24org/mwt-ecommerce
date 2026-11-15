<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;

final readonly class CodOperationsReportFiltersData
{
    /**
     * @param  array<int, OrderStatus|string>  $statuses
     * @param  array<int, FulfilmentStatus|string>  $fulfilmentStatuses
     * @param  array<int, PaymentStatus|string>  $paymentStatuses
     */
    public function __construct(
        public ReportDateRangeData $range,
        public array $statuses = [],
        public array $fulfilmentStatuses = [],
        public array $paymentStatuses = [],
        public ?string $search = null,
        public int $perPage = 25,
    ) {}

    /**
     * @return array<int, string>
     */
    public function statusValues(): array
    {
        return $this->enumValues(
            $this->statuses,
        );
    }

    /**
     * @return array<int, string>
     */
    public function fulfilmentStatusValues(): array
    {
        return $this->enumValues(
            $this->fulfilmentStatuses,
        );
    }

    /**
     * @return array<int, string>
     */
    public function paymentStatusValues(): array
    {
        return $this->enumValues(
            $this->paymentStatuses,
        );
    }

    public function normalizedSearch(): ?string
    {
        $search =
            trim(
                (string) $this->search,
            );

        return $search !== ''
            ? $search
            : null;
    }

    public function normalizedPerPage(): int
    {
        return in_array(
            $this->perPage,
            [
                10,
                25,
                50,
                100,
            ],
            true,
        )
            ? $this->perPage
            : 25;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'range' => $this->range->toArray(),

            'statuses' => $this->statusValues(),

            'fulfilment_statuses' => $this->fulfilmentStatusValues(),

            'payment_statuses' => $this->paymentStatusValues(),

            'search' => $this->normalizedSearch(),

            'per_page' => $this->normalizedPerPage(),
        ];
    }

    /**
     * @param  array<int, object|string>  $values
     * @return array<int, string>
     */
    private function enumValues(
        array $values,
    ): array {
        /** @var array<int, string> $normalized */
        $normalized = [];

        foreach (
            $values as $value
        ) {
            $normalizedValue =
                $value instanceof \BackedEnum
                ? (string) $value->value
                : (string) $value;

            if (
                ! in_array(
                    $normalizedValue,
                    $normalized,
                    true,
                )
            ) {
                $normalized[] =
                    $normalizedValue;
            }
        }

        return $normalized;
    }
}
