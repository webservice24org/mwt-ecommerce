<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class InventoryOperationsReportResultData
{
    /**
     * @param  array<int, InventoryOperationRowData>  $rows
     */
    public function __construct(
        public InventoryOperationsSummaryData $summary,
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
                    InventoryOperationRowData $row,
                ): array => $row->toArray(),

                $this->rows,
            ),

            'pagination' => $this
                ->pagination
                ->toArray(),
        ];
    }
}
