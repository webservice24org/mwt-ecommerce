<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Charts\BuildSalesAnalyticsCharts;
use App\Domain\Reporting\Data\SalesAnalyticsBreakdownRowData;
use App\Domain\Reporting\Data\SalesAnalyticsResultData;
use App\Domain\Reporting\Data\SalesAnalyticsSummaryData;
use App\Domain\Reporting\Data\SalesAnalyticsTrendRowData;
use PHPUnit\Framework\TestCase;

final class BuildSalesAnalyticsChartsTest extends TestCase
{
    public function test_it_builds_revenue_and_orders_trend_datasets(): void
    {
        $charts =
            (new BuildSalesAnalyticsCharts)
                ->build(
                    $this->report(),
                );

        $this->assertSame(
            'revenue_trend',
            $charts
                ->revenueTrend
                ->key,
        );

        $this->assertSame(
            'line',
            $charts
                ->revenueTrend
                ->type,
        );

        $this->assertCount(
            2,
            $charts
                ->revenueTrend
                ->series,
        );

        $this->assertCount(
            2,
            $charts
                ->revenueTrend
                ->points,
        );

        $firstRevenuePoint =
            $charts
                ->revenueTrend
                ->points[0];

        $this->assertSame(
            '2026-11-10',
            $firstRevenuePoint->key,
        );

        $this->assertSame(
            10_000,
            $firstRevenuePoint
                ->values[
                'grand_total'
            ],
        );

        $this->assertSame(
            10_000,
            $firstRevenuePoint
                ->values[
                'paid_revenue'
            ],
        );

        $firstOrdersPoint =
            $charts
                ->ordersTrend
                ->points[0];

        $this->assertSame(
            1,
            $firstOrdersPoint
                ->values[
                'orders_count'
            ],
        );

        $this->assertSame(
            2,
            $firstOrdersPoint
                ->values[
                'items_quantity'
            ],
        );
    }

    public function test_it_builds_status_payment_and_method_breakdowns(): void
    {
        $charts =
            (new BuildSalesAnalyticsCharts)
                ->build(
                    $this->report(),
                );

        $this->assertSame(
            'status_revenue',
            $charts
                ->statusRevenue
                ->key,
        );

        $this->assertSame(
            'bar',
            $charts
                ->statusRevenue
                ->type,
        );

        $this->assertSame(
            'completed',
            $charts
                ->statusRevenue
                ->points[0]
                ->key,
        );

        $this->assertSame(
            29_500,
            $charts
                ->statusRevenue
                ->points[0]
                ->values[
                'grand_total'
            ],
        );

        $this->assertSame(
            'paid',
            $charts
                ->paymentStatusRevenue
                ->points[0]
                ->key,
        );

        $this->assertSame(
            'cash_on_delivery',
            $charts
                ->paymentMethodRevenue
                ->points[0]
                ->key,
        );
    }

    public function test_chart_contract_serializes_for_frontend_and_pdf_consumers(): void
    {
        $charts =
            (new BuildSalesAnalyticsCharts)
                ->build(
                    $this->report(),
                );

        $data =
            $charts->toArray();

        $this->assertArrayHasKey(
            'revenue_trend',
            $data,
        );

        $this->assertArrayHasKey(
            'orders_trend',
            $data,
        );

        $this->assertArrayHasKey(
            'status_revenue',
            $data,
        );

        $this->assertArrayHasKey(
            'payment_status_revenue',
            $data,
        );

        $this->assertArrayHasKey(
            'payment_method_revenue',
            $data,
        );

        $this->assertSame(
            'money',
            $data[
                'revenue_trend'
            ][
                'series'
            ][0][
                'value_type'
            ],
        );
    }

    public function test_empty_report_produces_valid_empty_chart_datasets(): void
    {
        $report =
            new SalesAnalyticsResultData(
                summary: new SalesAnalyticsSummaryData(
                    ordersCount: 0,
                    itemsQuantity: 0,
                    subtotal: 0,
                    discountTotal: 0,
                    deliveryTotal: 0,
                    taxTotal: 0,
                    grandTotal: 0,
                    paidRevenue: 0,
                    averageOrderValue: 0,
                ),

                trend: [],

                statusBreakdown: [],

                paymentStatusBreakdown: [],

                paymentMethodBreakdown: [],
            );

        $charts =
            (new BuildSalesAnalyticsCharts)
                ->build(
                    $report,
                );

        $this->assertSame(
            [],
            $charts
                ->revenueTrend
                ->points,
        );

        $this->assertSame(
            [],
            $charts
                ->ordersTrend
                ->points,
        );

        $this->assertSame(
            [],
            $charts
                ->statusRevenue
                ->points,
        );
    }

    private function report(): SalesAnalyticsResultData
    {
        return new SalesAnalyticsResultData(
            summary: new SalesAnalyticsSummaryData(
                ordersCount: 2,
                itemsQuantity: 5,
                subtotal: 30_000,
                discountTotal: 3_000,
                deliveryTotal: 2_000,
                taxTotal: 500,
                grandTotal: 29_500,
                paidRevenue: 10_000,
                averageOrderValue: 14_750,
            ),

            trend: [
                new SalesAnalyticsTrendRowData(
                    date: '2026-11-10',
                    label: 'Nov 10, 2026',
                    ordersCount: 1,
                    itemsQuantity: 2,
                    subtotal: 10_000,
                    discountTotal: 1_000,
                    deliveryTotal: 800,
                    taxTotal: 200,
                    grandTotal: 10_000,
                    paidRevenue: 10_000,
                ),

                new SalesAnalyticsTrendRowData(
                    date: '2026-11-11',
                    label: 'Nov 11, 2026',
                    ordersCount: 1,
                    itemsQuantity: 3,
                    subtotal: 20_000,
                    discountTotal: 2_000,
                    deliveryTotal: 1_200,
                    taxTotal: 300,
                    grandTotal: 19_500,
                    paidRevenue: 0,
                ),
            ],

            statusBreakdown: [
                new SalesAnalyticsBreakdownRowData(
                    key: 'completed',
                    label: 'Completed',
                    ordersCount: 2,
                    grandTotal: 29_500,
                ),
            ],

            paymentStatusBreakdown: [
                new SalesAnalyticsBreakdownRowData(
                    key: 'paid',
                    label: 'Paid',
                    ordersCount: 1,
                    grandTotal: 10_000,
                ),

                new SalesAnalyticsBreakdownRowData(
                    key: 'due',
                    label: 'Due',
                    ordersCount: 1,
                    grandTotal: 19_500,
                ),
            ],

            paymentMethodBreakdown: [
                new SalesAnalyticsBreakdownRowData(
                    key: 'cash_on_delivery',
                    label: 'Cash On Delivery',
                    ordersCount: 2,
                    grandTotal: 29_500,
                ),
            ],
        );
    }
}
