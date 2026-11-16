<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class InventoryOperationsSummaryData
{
    public function __construct(
        public int $productsCount,
        public int $trackedProductsCount,

        public int $inStockCount,
        public int $lowStockCount,
        public int $outOfStockCount,

        public int $totalStockUnits,
        public int $lowStockThreshold,

        public int $attentionRequiredCount,
        public int $criticalAlertCount,
        public int $highAlertCount,
        public int $negativeStockCount,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'products_count' => $this->productsCount,

            'tracked_products_count' => $this->trackedProductsCount,

            'in_stock_count' => $this->inStockCount,

            'low_stock_count' => $this->lowStockCount,

            'out_of_stock_count' => $this->outOfStockCount,

            'total_stock_units' => $this->totalStockUnits,

            'low_stock_threshold' => $this->lowStockThreshold,

            'attention_required_count' => $this->attentionRequiredCount,

            'critical_alert_count' => $this->criticalAlertCount,

            'high_alert_count' => $this->highAlertCount,

            'negative_stock_count' => $this->negativeStockCount,
        ];
    }
}
