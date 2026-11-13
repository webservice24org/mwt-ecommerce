<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class DashboardBreakdownItemData
{
    public function __construct(
        public string $value,

        public string $label,

        public int $ordersCount,

        public int $total,
    ) {}

    /**
     * @return array{
     *     value: string,
     *     label: string,
     *     orders_count: int,
     *     total: int
     * }
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,

            'label' => $this->label,

            'orders_count' => $this->ordersCount,

            'total' => $this->total,
        ];
    }
}
