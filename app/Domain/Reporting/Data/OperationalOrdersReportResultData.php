<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class OperationalOrdersReportResultData
{
    /**
     * @param  array<int, OperationalOrderRowData>  $rows
     */
    public function __construct(
        public OperationalOrdersSummaryData $summary,
        public array $rows,
        public ReportPaginationData $pagination,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),

            'rows' => array_map(
                static fn (
                    OperationalOrderRowData $row,
                ): array => $row->toArray(),

                $this->rows,
            ),

            'pagination' => $this
                ->pagination
                ->toArray(),
        ];
    }
}
