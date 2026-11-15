<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Data\Charts\ReportChartData;

final readonly class SalesAnalyticsChartsData
{
    public function __construct(
        public ReportChartData $revenueTrend,
        public ReportChartData $ordersTrend,
        public ReportChartData $statusRevenue,
        public ReportChartData $paymentStatusRevenue,
        public ReportChartData $paymentMethodRevenue,
    ) {}

    /**
     * @return array{
     *     revenue_trend: array<string, mixed>,
     *     orders_trend: array<string, mixed>,
     *     status_revenue: array<string, mixed>,
     *     payment_status_revenue: array<string, mixed>,
     *     payment_method_revenue: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'revenue_trend' => $this
                ->revenueTrend
                ->toArray(),

            'orders_trend' => $this
                ->ordersTrend
                ->toArray(),

            'status_revenue' => $this
                ->statusRevenue
                ->toArray(),

            'payment_status_revenue' => $this
                ->paymentStatusRevenue
                ->toArray(),

            'payment_method_revenue' => $this
                ->paymentMethodRevenue
                ->toArray(),
        ];
    }
}
