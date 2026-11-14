<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Contracts\ReportExportRow;

final readonly class ReportExportData
{
    /**
     * @param  list<string>  $columns
     * @param  list<ReportExportRow>  $rows
     */
    public function __construct(
        public array $columns,
        public array $rows,
    ) {}

    public function rowCount(): int
    {
        return count(
            $this->rows,
        );
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    public function rowsAsArrays(): array
    {
        return array_map(
            static fn (
                ReportExportRow $row,
            ): array => $row->toArray(),
            $this->rows,
        );
    }

    /**
     * @return array{
     *     columns: list<string>,
     *     rows: list<array<string, int|string|null>>,
     *     row_count: int
     * }
     */
    public function toArray(): array
    {
        return [
            'columns' => $this->columns,

            'rows' => $this->rowsAsArrays(),

            'row_count' => $this->rowCount(),
        ];
    }
}
