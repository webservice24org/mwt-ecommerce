<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class ProductPerformanceReportResultData
{
    /**
     * @param  list<ProductPerformanceReportRowData>  $rows
     */
    public function __construct(
        public array $rows,

        public int $currentPage,

        public int $perPage,

        public int $total,

        public int $lastPage,

        public int $productGroupsCount,

        public int $quantitySold,

        public int $ordersCount,

        public int $grossSales,
    ) {}

    /**
     * @return array{
     *     data: list<array{
     *         product_id: int|null,
     *         product_name: string,
     *         sku: string|null,
     *         quantity_sold: int,
     *         orders_count: int,
     *         gross_sales: int
     *     }>,
     *     meta: array{
     *         current_page: int,
     *         per_page: int,
     *         total: int,
     *         last_page: int
     *     },
     *     summary: array{
     *         product_groups_count: int,
     *         quantity_sold: int,
     *         orders_count: int,
     *         gross_sales: int
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(
                static fn (
                    ProductPerformanceReportRowData $row,
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
            'product_groups_count' => $this->productGroupsCount,

            'quantity_sold' => $this->quantitySold,

            'orders_count' => $this->ordersCount,

            'gross_sales' => $this->grossSales,
            ],
        ];
    }
}
