<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum ReportExportFormat: string
{
    case Csv = 'csv';

    public function label(): string
    {
        return match ($this) {
            self::Csv => 'CSV',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::Csv => 'csv',
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=UTF-8',
        };
    }
}
