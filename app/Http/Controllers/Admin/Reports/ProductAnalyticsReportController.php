<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Charts\BuildProductAnalyticsCharts;
use App\Domain\Reporting\Queries\GetAdminProductAnalyticsReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductPerformanceReportRequest;
use Inertia\Inertia;
use Inertia\Response;

final class ProductAnalyticsReportController extends Controller
{
    public function __invoke(
        ProductPerformanceReportRequest $request,
        ReportDateRangeFactory $dateRanges,
        GetAdminProductAnalyticsReportQuery $reportQuery,
        BuildProductAnalyticsCharts $chartBuilder,
    ): Response {
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

        return Inertia::render(
            'Admin/Reports/ProductAnalytics/Index',
            [
                'filters' => $filters->toArray(),

                'query' => $request->validated(),

                /*
                 * IMPORTANT:
                 *
                 * Use exactly the same options array currently returned
                 * by ProductPerformanceReportController.
                 *
                 * Do not create a second set of labels/values.
                 */
                'options' => $this->options(),

                'report' => $report->toArray(),

                'charts' => $charts->toArray(),
            ],
        );
    }

    /**
     * Replace this method body with the exact option-building code
     * already used by ProductPerformanceReportController.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'presets' => [],

            'statuses' => [],

            'payment_statuses' => [],

            'per_page' => [
                10,
                25,
                50,
                100,
            ],
        ];
    }
}
