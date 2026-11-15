<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class SalesAnalyticsTrendRowData
{
    public function __construct(
        public string $date,
        public string $label,
        public int $ordersCount,
        public int $itemsQuantity,
        public int $subtotal,
        public int $discountTotal,
        public int $deliveryTotal,
        public int $taxTotal,
        public int $grandTotal,
        public int $paidRevenue,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,

            'label' => $this->label,

            'orders_count' => $this->ordersCount,

            'items_quantity' => $this->itemsQuantity,

            'subtotal' => $this->subtotal,

            'discount_total' => $this->discountTotal,

            'delivery_total' => $this->deliveryTotal,

            'tax_total' => $this->taxTotal,

            'grand_total' => $this->grandTotal,

            'paid_revenue' => $this->paidRevenue,
        ];
    }
}
