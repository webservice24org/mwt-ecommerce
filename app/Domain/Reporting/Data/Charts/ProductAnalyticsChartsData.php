<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data\Charts;

final readonly class ProductAnalyticsChartsData
{
    public function __construct(
        public ReportChartData $salesTrend,
        public ReportChartData $topProductsByRevenue,
        public ReportChartData $topProductsByQuantity,
        public ReportChartData $currentVsHistoricalRevenue,
        public ReportChartData $skuPerformance,
        public ReportChartData $revenueShareByProduct,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        return [
            'sales_trend' => $this->salesTrend->toArray(),

            'top_products_by_revenue' => $this->topProductsByRevenue->toArray(),

            'top_products_by_quantity' => $this->topProductsByQuantity->toArray(),

            'current_vs_historical_revenue' => $this->currentVsHistoricalRevenue->toArray(),

            'sku_performance' => $this->skuPerformance->toArray(),

            'revenue_share_by_product' => $this->revenueShareByProduct->toArray(),
        ];
    }
}
