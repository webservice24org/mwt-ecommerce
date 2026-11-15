<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Charts\BuildSalesAnalyticsCharts;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminSalesAnalyticsReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesReportRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SalesAnalyticsReportController extends Controller
{
    public function __invoke(
        Request $request,
        SalesReportRequest $reportRequest,
        GetAdminSalesAnalyticsReportQuery $reportQuery,
        BuildSalesAnalyticsCharts $chartBuilder,
        ReportDateRangeFactory $dateRanges,
    ): Response {

        $filters = $reportRequest->filters($dateRanges);

        $report =
            $reportQuery->get(
                $filters,
            );

        $charts =
            $chartBuilder->build(
                $report,
            );

        return Inertia::render(
            'Admin/Reports/SalesAnalytics/Index',
            [
                'filters' => $filters->toArray(),

                'query' => $reportRequest
                    ->queryParameters(),

                'options' => [
                    'presets' => ReportDatePreset::options(),

                    'statuses' => $this->orderStatusOptions(),

                    'payment_statuses' => $this->paymentStatusOptions(),
                ],

                'report' => $report->toArray(),

                'charts' => $charts->toArray(),
            ],
        );
    }

    /**
     * @return list<array{
     *     value: string,
     *     label: string
     * }>
     */
    private function orderStatusOptions(): array
    {
        return array_map(
            static fn ($status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            OrderStatus::cases(),
        );
    }

    /**
     * @return list<array{
     *     value: string,
     *     label: string
     * }>
     */
    private function paymentStatusOptions(): array
    {
        return array_map(
            static fn ($status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            PaymentStatus::cases(),
        );
    }
}
