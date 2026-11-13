<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class AdminDashboardBreakdownsData
{
    /**
     * @param  list<DashboardBreakdownItemData>  $payment
     * @param  list<DashboardBreakdownItemData>  $fulfilment
     */
    public function __construct(
        public array $payment,

        public array $fulfilment,
    ) {}

    /**
     * @return array{
     *     payment: list<array{
     *         value: string,
     *         label: string,
     *         orders_count: int,
     *         total: int
     *     }>,
     *     fulfilment: list<array{
     *         value: string,
     *         label: string,
     *         orders_count: int,
     *         total: int
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'payment' => array_map(
                static fn (
                    DashboardBreakdownItemData $item,
                ): array => $item->toArray(),
                $this->payment,
            ),

            'fulfilment' => array_map(
                static fn (
                    DashboardBreakdownItemData $item,
                ): array => $item->toArray(),
                $this->fulfilment,
            ),
        ];
    }
}
