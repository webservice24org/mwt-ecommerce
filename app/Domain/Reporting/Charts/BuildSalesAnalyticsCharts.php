<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Charts;

use App\Domain\Reporting\Data\Charts\ReportChartData;
use App\Domain\Reporting\Data\Charts\ReportChartPointData;
use App\Domain\Reporting\Data\Charts\ReportChartSeriesData;
use App\Domain\Reporting\Data\SalesAnalyticsBreakdownRowData;
use App\Domain\Reporting\Data\SalesAnalyticsChartsData;
use App\Domain\Reporting\Data\SalesAnalyticsResultData;
use App\Domain\Reporting\Data\SalesAnalyticsTrendRowData;

final class BuildSalesAnalyticsCharts
{
    public function build(
        SalesAnalyticsResultData $report,
    ): SalesAnalyticsChartsData {
        return new SalesAnalyticsChartsData(
            revenueTrend: $this->revenueTrend(
                $report,
            ),

            ordersTrend: $this->ordersTrend(
                $report,
            ),

            statusRevenue: $this->breakdownChart(
                key: 'status_revenue',
                title: 'Revenue by Order Status',
                description: 'Grand total contribution grouped by order status.',
                rows: $report->statusBreakdown,
            ),

            paymentStatusRevenue: $this->breakdownChart(
                key: 'payment_status_revenue',
                title: 'Revenue by Payment Status',
                description: 'Grand total contribution grouped by payment status.',
                rows: $report->paymentStatusBreakdown,
            ),

            paymentMethodRevenue: $this->breakdownChart(
                key: 'payment_method_revenue',
                title: 'Revenue by Payment Method',
                description: 'Grand total contribution grouped by payment method.',
                rows: $report->paymentMethodBreakdown,
            ),
        );
    }

    private function revenueTrend(
        SalesAnalyticsResultData $report,
    ): ReportChartData {
        return new ReportChartData(
            key: 'revenue_trend',

            title: 'Revenue Trend',

            description: 'Daily grand total and paid revenue across the selected reporting period.',

            type: 'line',

            series: [
                new ReportChartSeriesData(
                    key: 'grand_total',
                    label: 'Revenue',
                    valueType: 'money',
                ),

                new ReportChartSeriesData(
                    key: 'paid_revenue',
                    label: 'Paid Revenue',
                    valueType: 'money',
                ),
            ],

            points: array_map(
                static fn (
                    SalesAnalyticsTrendRowData $row,
                ): ReportChartPointData => new ReportChartPointData(
                    key: $row->date,

                    label: $row->label,

                    values: [
                        'grand_total' => $row->grandTotal,

                        'paid_revenue' => $row->paidRevenue,
                    ],
                ),
                $report->trend,
            ),
        );
    }

    private function ordersTrend(
        SalesAnalyticsResultData $report,
    ): ReportChartData {
        return new ReportChartData(
            key: 'orders_trend',

            title: 'Orders Trend',

            description: 'Daily order volume and item quantity across the selected reporting period.',

            type: 'line',

            series: [
                new ReportChartSeriesData(
                    key: 'orders_count',
                    label: 'Orders',
                    valueType: 'integer',
                ),

                new ReportChartSeriesData(
                    key: 'items_quantity',
                    label: 'Items',
                    valueType: 'integer',
                ),
            ],

            points: array_map(
                static fn (
                    SalesAnalyticsTrendRowData $row,
                ): ReportChartPointData => new ReportChartPointData(
                    key: $row->date,

                    label: $row->label,

                    values: [
                        'orders_count' => $row->ordersCount,

                        'items_quantity' => $row->itemsQuantity,
                    ],
                ),
                $report->trend,
            ),
        );
    }

    /**
     * @param  list<SalesAnalyticsBreakdownRowData>  $rows
     */
    private function breakdownChart(
        string $key,
        string $title,
        string $description,
        array $rows,
    ): ReportChartData {
        return new ReportChartData(
            key: $key,

            title: $title,

            description: $description,

            type: 'bar',

            series: [
                new ReportChartSeriesData(
                    key: 'grand_total',
                    label: 'Revenue',
                    valueType: 'money',
                ),

                new ReportChartSeriesData(
                    key: 'orders_count',
                    label: 'Orders',
                    valueType: 'integer',
                ),
            ],

            points: array_map(
                static fn (
                    SalesAnalyticsBreakdownRowData $row,
                ): ReportChartPointData => new ReportChartPointData(
                    key: $row->key,

                    label: $row->label,

                    values: [
                        'grand_total' => $row->grandTotal,

                        'orders_count' => $row->ordersCount,
                    ],
                ),
                $rows,
            ),
        );
    }
}
