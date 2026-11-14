<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Enums\ReportExportFormat;
use App\Domain\Reporting\Export\ReportExportFilenameFactory;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use Tests\TestCase;

final class ReportExportFilenameFactoryTest extends TestCase
{
    public function test_sales_filename_is_deterministic(): void
    {
        $range =
            app(
                ReportDateRangeFactory::class,
            )->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-30',

                timezone: 'Asia/Dhaka',
            );

        $filename =
            app(
                ReportExportFilenameFactory::class,
            )->sales(
                $range,
                ReportExportFormat::Csv,
            );

        $this->assertSame(
            'sales-report-2026-11-01-to-2026-11-30.csv',
            $filename,
        );
    }

    public function test_product_performance_filename_is_deterministic(): void
    {
        $range =
            app(
                ReportDateRangeFactory::class,
            )->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-30',

                timezone: 'Asia/Dhaka',
            );

        $filename =
            app(
                ReportExportFilenameFactory::class,
            )->productPerformance(
                $range,
                ReportExportFormat::Csv,
            );

        $this->assertSame(
            'product-performance-report-2026-11-01-to-2026-11-30.csv',
            $filename,
        );
    }
}
