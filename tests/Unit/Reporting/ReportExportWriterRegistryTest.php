<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Enums\ReportExportFormat;
use App\Domain\Reporting\Export\CsvReportExportWriter;
use App\Domain\Reporting\Export\ReportExportWriterRegistry;
use App\Domain\Reporting\Support\SpreadsheetSafeValueSerializer;
use PHPUnit\Framework\TestCase;

final class ReportExportWriterRegistryTest extends TestCase
{
    public function test_registry_resolves_csv_writer(): void
    {
        $csv =
            new CsvReportExportWriter(
                new SpreadsheetSafeValueSerializer,
            );

        $registry =
            new ReportExportWriterRegistry(
                [
                    $csv,
                ],
            );

        $this->assertSame(
            $csv,
            $registry->forFormat(
                ReportExportFormat::Csv,
            ),
        );
    }
}
