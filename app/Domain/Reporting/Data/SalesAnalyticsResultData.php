<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class SalesAnalyticsResultData
{
    /**
     * @param  list<SalesAnalyticsTrendRowData>  $trend
     * @param  list<SalesAnalyticsBreakdownRowData>  $statusBreakdown
     * @param  list<SalesAnalyticsBreakdownRowData>  $paymentStatusBreakdown
     * @param  list<SalesAnalyticsBreakdownRowData>  $paymentMethodBreakdown
     */
    public function __construct(
        public SalesAnalyticsSummaryData $summary,
        public array $trend,
        public array $statusBreakdown,
        public array $paymentStatusBreakdown,
        public array $paymentMethodBreakdown,
    ) {}

    /**
     * @return array{
     *     summary: array<string, int>,
     *     trend: list<array<string, int|string>>,
     *     status_breakdown: list<array<string, int|string>>,
     *     payment_status_breakdown: list<array<string, int|string>>,
     *     payment_method_breakdown: list<array<string, int|string>>
     * }
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),

            'trend' => array_map(
                static fn (
                    SalesAnalyticsTrendRowData $row,
                ): array => $row->toArray(),
                $this->trend,
            ),

            'status_breakdown' => array_map(
                static fn (
                    SalesAnalyticsBreakdownRowData $row,
                ): array => $row->toArray(),
                $this->statusBreakdown,
            ),

            'payment_status_breakdown' => array_map(
                static fn (
                    SalesAnalyticsBreakdownRowData $row,
                ): array => $row->toArray(),
                $this->paymentStatusBreakdown,
            ),

            'payment_method_breakdown' => array_map(
                static fn (
                    SalesAnalyticsBreakdownRowData $row,
                ): array => $row->toArray(),
                $this->paymentMethodBreakdown,
            ),
        ];
    }
}
