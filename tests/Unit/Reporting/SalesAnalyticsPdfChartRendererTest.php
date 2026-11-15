<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Data\Charts\ReportChartData;
use App\Domain\Reporting\Data\Charts\ReportChartPointData;
use App\Domain\Reporting\Data\Charts\ReportChartSeriesData;
use App\Domain\Reporting\Pdf\SalesAnalyticsPdfChartRenderer;
use PHPUnit\Framework\TestCase;

final class SalesAnalyticsPdfChartRendererTest extends TestCase
{
    public function test_it_renders_line_chart_as_svg(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'revenue_trend',

                title: 'Revenue Trend',

                description: 'Daily revenue trend.',

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

                points: [
                    new ReportChartPointData(
                        key: '2026-11-10',

                        label: 'Nov 10, 2026',

                        values: [
                            'grand_total' => 10_000,

                            'paid_revenue' => 8_000,
                        ],
                    ),

                    new ReportChartPointData(
                        key: '2026-11-11',

                        label: 'Nov 11, 2026',

                        values: [
                            'grand_total' => 20_000,

                            'paid_revenue' => 15_000,
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            '<svg',
            $svg,
        );

        $this->assertStringContainsString(
            '<polyline',
            $svg,
        );

        $this->assertStringContainsString(
            '<circle',
            $svg,
        );

        $this->assertStringContainsString(
            'Revenue',
            $svg,
        );

        $this->assertStringContainsString(
            'Paid Revenue',
            $svg,
        );

        $this->assertStringContainsString(
            'BDT',
            $svg,
        );

        $this->assertStringContainsString(
            '</svg>',
            $svg,
        );
    }

    public function test_it_renders_bar_chart_as_svg(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'status_revenue',

                title: 'Revenue by Order Status',

                description: 'Revenue grouped by order status.',

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

                points: [
                    new ReportChartPointData(
                        key: 'completed',

                        label: 'Completed',

                        values: [
                            'grand_total' => 30_000,

                            'orders_count' => 3,
                        ],
                    ),

                    new ReportChartPointData(
                        key: 'processing',

                        label: 'Processing',

                        values: [
                            'grand_total' => 15_000,

                            'orders_count' => 2,
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            '<svg',
            $svg,
        );

        $this->assertStringContainsString(
            '<rect',
            $svg,
        );

        $this->assertStringContainsString(
            'Completed',
            $svg,
        );

        $this->assertStringContainsString(
            'Processing',
            $svg,
        );

        $this->assertStringContainsString(
            '3 orders',
            $svg,
        );

        $this->assertStringContainsString(
            '2 orders',
            $svg,
        );

        $this->assertStringContainsString(
            '</svg>',
            $svg,
        );
    }

    public function test_empty_chart_returns_valid_svg_placeholder(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'revenue_trend',

                title: 'Revenue Trend',

                description: 'Daily revenue trend.',

                type: 'line',

                series: [
                    new ReportChartSeriesData(
                        key: 'grand_total',

                        label: 'Revenue',

                        valueType: 'money',
                    ),
                ],

                points: [],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            '<svg',
            $svg,
        );

        $this->assertStringContainsString(
            'Revenue Trend',
            $svg,
        );

        $this->assertStringContainsString(
            'No data available for the selected filters.',
            $svg,
        );

        $this->assertStringContainsString(
            '</svg>',
            $svg,
        );
    }

    public function test_non_numeric_chart_values_are_safely_treated_as_zero(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'orders_trend',

                title: 'Orders Trend',

                description: 'Daily orders.',

                type: 'line',

                series: [
                    new ReportChartSeriesData(
                        key: 'orders_count',

                        label: 'Orders',

                        valueType: 'integer',
                    ),
                ],

                points: [
                    new ReportChartPointData(
                        key: '2026-11-10',

                        label: 'Nov 10, 2026',

                        values: [
                            'orders_count' => 'invalid',
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            '<svg',
            $svg,
        );

        $this->assertStringContainsString(
            '<polyline',
            $svg,
        );
    }

    public function test_chart_labels_are_html_escaped(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'status_revenue',

                title: '<script>alert("title")</script>',

                description: 'Unsafe test.',

                type: 'bar',

                series: [
                    new ReportChartSeriesData(
                        key: 'grand_total',

                        label: '<script>alert("series")</script>',

                        valueType: 'money',
                    ),
                ],

                points: [
                    new ReportChartPointData(
                        key: 'unsafe',

                        label: '<img src=x onerror=alert(1)>',

                        values: [
                            'grand_total' => 10_000,
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringNotContainsString(
            '<script>',
            $svg,
        );

        $this->assertStringNotContainsString(
            '<img',
            $svg,
        );

        $this->assertStringContainsString(
            '&lt;img src=x on',
            $svg,
        );
    }

    public function test_empty_chart_title_is_escaped(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'empty',

                title: '<script>alert(1)</script>',

                description: 'Empty unsafe chart.',

                type: 'line',

                series: [],

                points: [],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringNotContainsString(
            '<script>',
            $svg,
        );

        $this->assertStringContainsString(
            '&lt;script&gt;',
            $svg,
        );
    }

    public function test_money_axis_values_are_rendered_in_bdt_minor_unit_format(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'revenue_trend',

                title: 'Revenue Trend',

                description: 'Revenue chart.',

                type: 'line',

                series: [
                    new ReportChartSeriesData(
                        key: 'grand_total',

                        label: 'Revenue',

                        valueType: 'money',
                    ),
                ],

                points: [
                    new ReportChartPointData(
                        key: '2026-11-10',

                        label: 'Nov 10',

                        values: [
                            'grand_total' => 100_000,
                        ],
                    ),

                    new ReportChartPointData(
                        key: '2026-11-11',

                        label: 'Nov 11',

                        values: [
                            'grand_total' => 200_000,
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            'BDT',
            $svg,
        );

        $this->assertStringContainsString(
            '2.0K',
            $svg,
        );
    }

    public function test_integer_axis_values_do_not_use_bdt_prefix(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'orders_trend',

                title: 'Orders Trend',

                description: 'Orders chart.',

                type: 'line',

                series: [
                    new ReportChartSeriesData(
                        key: 'orders_count',

                        label: 'Orders',

                        valueType: 'integer',
                    ),
                ],

                points: [
                    new ReportChartPointData(
                        key: '2026-11-10',

                        label: 'Nov 10',

                        values: [
                            'orders_count' => 100,
                        ],
                    ),

                    new ReportChartPointData(
                        key: '2026-11-11',

                        label: 'Nov 11',

                        values: [
                            'orders_count' => 200,
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            '<svg',
            $svg,
        );

        $this->assertStringNotContainsString(
            'BDT',
            $svg,
        );
    }

    public function test_long_x_axis_labels_are_truncated(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'payment_method_revenue',

                title: 'Revenue by Payment Method',

                description: 'Revenue grouped by payment method.',

                type: 'bar',

                series: [
                    new ReportChartSeriesData(
                        key: 'grand_total',

                        label: 'Revenue',

                        valueType: 'money',
                    ),
                ],

                points: [
                    new ReportChartPointData(
                        key: 'very_long_method',

                        label: 'This Is An Extremely Long Payment Method Label',

                        values: [
                            'grand_total' => 10_000,
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            '…',
            $svg,
        );

        $this->assertStringNotContainsString(
            'This Is An Extremely Long Payment Method Label',
            $svg,
        );
    }

    public function test_unknown_chart_type_returns_placeholder_svg(): void
    {
        $renderer =
            new SalesAnalyticsPdfChartRenderer;

        $chart =
            new ReportChartData(
                key: 'unsupported',

                title: 'Unsupported Chart',

                description: 'Unsupported chart type.',

                type: 'pie',

                series: [
                    new ReportChartSeriesData(
                        key: 'grand_total',

                        label: 'Revenue',

                        valueType: 'money',
                    ),
                ],

                points: [
                    new ReportChartPointData(
                        key: 'one',

                        label: 'One',

                        values: [
                            'grand_total' => 10_000,
                        ],
                    ),
                ],
            );

        $svg =
            $renderer->render(
                $chart,
            );

        $this->assertStringContainsString(
            '<svg',
            $svg,
        );

        $this->assertStringContainsString(
            'Unsupported Chart',
            $svg,
        );

        $this->assertStringContainsString(
            'No data available for the selected filters.',
            $svg,
        );
    }
}
