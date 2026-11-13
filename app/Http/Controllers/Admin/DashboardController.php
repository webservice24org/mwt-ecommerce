<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Reporting\Data\DashboardInventoryAlertData;
use App\Domain\Reporting\Data\DashboardRecentOrderData;
use App\Domain\Reporting\Data\DashboardTopProductData;
use App\Domain\Reporting\Data\DashboardTrendPointData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardBreakdownsQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardInventoryAlertsQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardInventorySummaryQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardMetricsQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardOperationalSummaryQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardRecentOrdersQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardTopProductsQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardTrendQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardReportRequest;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(
        DashboardReportRequest $request,
        ReportDateRangeFactory $dateRanges,
        GetAdminDashboardMetricsQuery $metricsQuery,
        GetAdminDashboardTrendQuery $trendQuery,
        GetAdminDashboardBreakdownsQuery $breakdownsQuery,
        GetAdminDashboardRecentOrdersQuery $recentOrdersQuery,
        GetAdminDashboardOperationalSummaryQuery $operationalSummaryQuery,
        GetAdminDashboardTopProductsQuery $topProductsQuery,
        GetAdminDashboardInventoryAlertsQuery $inventoryAlertsQuery,
        GetAdminDashboardInventorySummaryQuery $inventorySummaryQuery,
    ): Response {
        $range =
            $dateRanges->make(
                preset: $request->preset(),

                from: $request->fromDate(),

                to: $request->toDate(),

                timezone: $request->timezone(),
            );

        $metrics =
            $metricsQuery->get(
                $range,
            );

        $trend =
            array_map(
                static fn (
                    DashboardTrendPointData $point,
                ): array => $point->toArray(),
                $trendQuery->get(
                    $range,
                ),
            );

        $breakdowns =
            $breakdownsQuery->get(
                $range,
            );

        $recentOrders =
            array_map(
                static fn (
                    DashboardRecentOrderData $order,
                ): array => $order->toArray(),
                $recentOrdersQuery->get(
                    range: $range,

                    limit: 8,
                ),
            );

        $operationalSummary =
            $operationalSummaryQuery->get(
                $range,
            );

        $topProducts =
            array_map(
                static fn (
                    DashboardTopProductData $product,
                ): array => $product->toArray(),
                $topProductsQuery->get(
                    range: $range,

                    limit: 10,
                ),
            );

        $inventoryThreshold =
            5;

        $inventoryAlerts =
            array_map(
                static fn (
                    DashboardInventoryAlertData $alert,
                ): array => $alert->toArray(),
                $inventoryAlertsQuery->get(
                    threshold: $inventoryThreshold,

                    limit: 10,
                ),
            );

        $inventorySummary =
            $inventorySummaryQuery->get(
                lowStockThreshold: $inventoryThreshold,
            );

        return Inertia::render(
            'Admin/Dashboard',
            [
                'filters' => [
                    'presets' => ReportDatePreset::options(),

                    'range' => $range->toArray(),
                ],

                'metrics' => $metrics->toArray(),

                'trend' => $trend,

                'breakdowns' => $breakdowns->toArray(),

                'recentOrders' => $recentOrders,

                'operationalSummary' => $operationalSummary
                    ->toArray(),

                'topProducts' => $topProducts,

                'inventorySummary' => $inventorySummary
                    ->toArray(),

                'inventoryAlerts' => $inventoryAlerts,
            ],
        );
    }
}
