<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class SalesReportResultData
{
    /**
     * @param  list<SalesReportRowData>  $rows
     */
    public function __construct(
        public array $rows,

        public int $currentPage,

        public int $perPage,

        public int $total,

        public int $lastPage,

        public int $subtotal,

        public int $discountTotal,

        public int $deliveryTotal,

        public int $taxTotal,

        public int $grandTotal,

        public int $itemsQuantity,
    ) {}

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{
     *         current_page: int,
     *         per_page: int,
     *         total: int,
     *         last_page: int
     *     },
     *     summary: array{
     *         subtotal: int,
     *         discount_total: int,
     *         delivery_total: int,
     *         tax_total: int,
     *         grand_total: int,
     *         items_quantity: int
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(
                static fn (
                    SalesReportRowData $row,
                ): array => $row->toArray(),
                $this->rows,
            ),

            'meta' => [
            'current_page' => $this->currentPage,

            'per_page' => $this->perPage,

            'total' => $this->total,

            'last_page' => $this->lastPage,
            ],

            'summary' => [
            'subtotal' => $this->subtotal,

            'discount_total' => $this->discountTotal,

            'delivery_total' => $this->deliveryTotal,

            'tax_total' => $this->taxTotal,

            'grand_total' => $this->grandTotal,

            'items_quantity' => $this->itemsQuantity,
            ],
        ];
    }
}
