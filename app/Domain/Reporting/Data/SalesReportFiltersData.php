<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;

final readonly class SalesReportFiltersData
{
    /**
     * @param  list<OrderStatus>  $statuses
     * @param  list<PaymentStatus>  $paymentStatuses
     */
    public function __construct(
        public ReportDateRangeData $range,

        public array $statuses = [],

        public array $paymentStatuses = [],

        public ?string $search = null,

        public int $perPage = 25,
    ) {}

    /**
     * @return list<string>
     */
    public function statusValues(): array
    {
        return array_map(
            static fn (
                OrderStatus $status,
            ): string => $status->value,
            $this->statuses,
        );
    }

    /**
     * @return list<string>
     */
    public function paymentStatusValues(): array
    {
        return array_map(
            static fn (
                PaymentStatus $status,
            ): string => $status->value,
            $this->paymentStatuses,
        );
    }

    public function normalizedSearch(): ?string
    {
        if (
            $this->search === null
        ) {
            return null;
        }

        $search =
            trim(
                $this->search,
            );

        return $search !== ''
            ? $search
            : null;
    }

    public function normalizedPerPage(): int
    {
        return max(
            10,
            min(
                $this->perPage,
                100,
            ),
        );
    }

    /**
     * @return array{
     *     range: array{
     *         preset: string,
     *         preset_label: string,
     *         from: string,
     *         to: string,
     *         timezone: string
     *     },
     *     statuses: list<string>,
     *     payment_statuses: list<string>,
     *     search: string|null,
     *     per_page: int
     * }
     */
    public function toArray(): array
    {
        return [
            'range' => $this->range
                ->toArray(),

            'statuses' => $this->statusValues(),

            'payment_statuses' => $this->paymentStatusValues(),

            'search' => $this->normalizedSearch(),

            'per_page' => $this->normalizedPerPage(),
        ];
    }
}
