<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Contracts;

use App\Domain\Reporting\Data\ReportExportData;
use App\Domain\Reporting\Enums\ReportExportFormat;

interface ReportExportWriter
{
    public function format(): ReportExportFormat;

    public function write(
        ReportExportData $export,
    ): string;
}
