<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class SalesAnalyticsBreakdownRowData
{
    public function __construct(
        public string $key,
        public string $label,
        public int $ordersCount,
        public int $grandTotal,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,

            'label' => $this->label,

            'orders_count' => $this->ordersCount,

            'grand_total' => $this->grandTotal,
        ];
    }
}
