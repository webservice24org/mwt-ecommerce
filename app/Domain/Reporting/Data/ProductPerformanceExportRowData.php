<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Contracts\ReportExportRow;

final readonly class ProductPerformanceExportRowData implements ReportExportRow
{
    public function __construct(
        public ?int $productId,

        public string $productName,

        public ?string $sku,

        public bool $historical,

        public int $quantitySold,

        public int $ordersCount,

        public int $grossSales,
    ) {}

    /**
     * @return array{
     *     product_id: int|null,
     *     product_name: string,
     *     sku: string|null,
     *     historical: int,
     *     quantity_sold: int,
     *     orders_count: int,
     *     gross_sales: int
     * }
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,

            'product_name' => $this->productName,

            'sku' => $this->sku,

            /*
             * Export as deterministic
             * machine-friendly 1/0.
             */
            'historical' => $this->historical
                ? 1
                : 0,

            'quantity_sold' => $this->quantitySold,

            'orders_count' => $this->ordersCount,

            'gross_sales' => $this->grossSales,
        ];
    }
}
