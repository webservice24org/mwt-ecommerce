<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Export;

use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportExportFormat;

final readonly class ReportExportFilenameFactory
{
    public function sales(
        ReportDateRangeData $range,
        ReportExportFormat $format,
    ): string {
        return $this->make(
            prefix: 'sales-report',
            range: $range,
            format: $format,
        );
    }

    public function productPerformance(
        ReportDateRangeData $range,
        ReportExportFormat $format,
    ): string {
        return $this->make(
            prefix: 'product-performance-report',

            range: $range,

            format: $format,
        );
    }

    private function make(
        string $prefix,
        ReportDateRangeData $range,
        ReportExportFormat $format,
    ): string {
        return sprintf(
            '%s-%s-to-%s.%s',
            $prefix,
            $range->fromDate(),
            $range->toDate(),
            $format->extension(),
        );
    }
}
