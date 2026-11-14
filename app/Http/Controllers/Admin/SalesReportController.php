<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminSalesReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesReportRequest;
use Inertia\Inertia;
use Inertia\Response;

final class SalesReportController extends Controller
{
    public function __invoke(
        SalesReportRequest $request,
        ReportDateRangeFactory $dateRanges,
        GetAdminSalesReportQuery $salesReportQuery,
    ): Response {
        $filters =
            $request->filters(
                $dateRanges,
            );

        $report =
            $salesReportQuery->get(
                filters: $filters,

                page: $request->pageNumber(),
            );

        return Inertia::render(
            'Admin/Reports/Sales/Index',
            [
                'filters' => $filters->toArray(),

                'query' => $request->queryParameters(),

                'options' => [
                    'presets' => ReportDatePreset::options(),

                    'statuses' => $this->orderStatusOptions(),

                    'payment_statuses' => $this->paymentStatusOptions(),

                    'per_page' => [
                        10,
                        25,
                        50,
                        100,
                    ],
                ],

                'report' => $report->toArray(),
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
            static fn (
                OrderStatus $status,
            ): array => [
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
            static fn (
                PaymentStatus $status,
            ): array => [
                'value' => $status->value,

                'label' => $status->label(),
            ],
            PaymentStatus::cases(),
        );
    }
}
