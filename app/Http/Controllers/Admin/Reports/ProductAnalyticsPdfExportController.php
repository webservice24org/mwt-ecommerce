<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Charts\BuildProductAnalyticsCharts;
use App\Domain\Reporting\Pdf\SalesAnalyticsPdfChartRenderer;
use App\Domain\Reporting\Queries\GetAdminProductAnalyticsReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductPerformanceReportRequest;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final class ProductAnalyticsPdfExportController extends Controller
{
    public function __invoke(
        ProductPerformanceReportRequest $request,
        ReportDateRangeFactory $dateRanges,
        GetAdminProductAnalyticsReportQuery $reportQuery,
        BuildProductAnalyticsCharts $chartBuilder,
        SalesAnalyticsPdfChartRenderer $chartRenderer,
    ): PdfBuilder {
        $filters =
            $request->filters(
                $dateRanges,
            );

        $report =
            $reportQuery->get(
                $filters,
            );

        $charts =
            $chartBuilder->build(
                $report,
            );

        $chartSvgs = [
            'sales_trend' => $chartRenderer->render(
                $charts->salesTrend,
            ),

            'top_products_by_revenue' => $chartRenderer->render(
                $charts->topProductsByRevenue,
            ),

            'top_products_by_quantity' => $chartRenderer->render(
                $charts->topProductsByQuantity,
            ),

            'current_vs_historical_revenue' => $chartRenderer->render(
                $charts->currentVsHistoricalRevenue,
            ),

            'sku_performance' => $chartRenderer->render(
                $charts->skuPerformance,
            ),

            'revenue_share_by_product' => $chartRenderer->render(
                $charts->revenueShareByProduct,
            ),
        ];

        $filename =
            sprintf(
                'product-analytics-%s-to-%s.pdf',
                $filters
                    ->range
                    ->fromDate(),
                $filters
                    ->range
                    ->toDate(),
            );

        return Pdf::view(
            'admin.reports.product-analytics-pdf',
            [
                'filters' => $filters,

                'report' => $report,

                'charts' => $charts,

                'chartSvgs' => $chartSvgs,
            ],
        )
            ->format(
                'a4',
            )
            ->landscape()
            ->margins(
                10,
                10,
                12,
                10,
            )
            ->meta(
                title: 'Product Analytics Report',

                author: (string) config(
                    'app.name',
                ),

                subject: 'Product analytics dashboard report',
            )
            ->name(
                $filename,
            );
    }
}
