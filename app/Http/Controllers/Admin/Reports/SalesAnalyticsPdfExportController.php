<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Charts\BuildSalesAnalyticsCharts;
use App\Domain\Reporting\Pdf\SalesAnalyticsPdfChartRenderer;
use App\Domain\Reporting\Queries\GetAdminSalesAnalyticsReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesReportRequest;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final class SalesAnalyticsPdfExportController extends Controller
{
    public function __invoke(
        SalesReportRequest $request,
        ReportDateRangeFactory $dateRanges,
        GetAdminSalesAnalyticsReportQuery $reportQuery,
        BuildSalesAnalyticsCharts $chartBuilder,
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
            'revenue_trend' => $chartRenderer->render(
                $charts->revenueTrend,
            ),

            'orders_trend' => $chartRenderer->render(
                $charts->ordersTrend,
            ),

            'status_revenue' => $chartRenderer->render(
                $charts->statusRevenue,
            ),

            'payment_status_revenue' => $chartRenderer->render(
                $charts->paymentStatusRevenue,
            ),

            'payment_method_revenue' => $chartRenderer->render(
                $charts->paymentMethodRevenue,
            ),
        ];

        $filename =
            sprintf(
                'sales-analytics-%s-to-%s.pdf',
                $filters
                    ->range
                    ->fromDate(),
                $filters
                    ->range
                    ->toDate(),
            );

        return Pdf::view(
            'admin.reports.sales-analytics-pdf',
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
                title: 'Sales Analytics Report',

                author: (string) config(
                    'app.name',
                ),

                subject: 'Sales analytics dashboard report',
            )
            ->name(
                $filename,
            );
    }
}
