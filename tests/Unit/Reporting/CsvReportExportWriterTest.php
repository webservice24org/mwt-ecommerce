<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Data\ProductPerformanceExportRowData;
use App\Domain\Reporting\Data\ReportExportData;
use App\Domain\Reporting\Enums\ReportExportFormat;
use App\Domain\Reporting\Export\CsvReportExportWriter;
use App\Domain\Reporting\Support\SpreadsheetSafeValueSerializer;
use PHPUnit\Framework\TestCase;

final class CsvReportExportWriterTest extends TestCase
{
    private CsvReportExportWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer =
            new CsvReportExportWriter(
                new SpreadsheetSafeValueSerializer,
            );
    }

    public function test_writer_exposes_csv_format(): void
    {
        $this->assertSame(
            ReportExportFormat::Csv,
            $this->writer->format(),
        );
    }

    public function test_csv_starts_with_utf8_bom(): void
    {
        $csv =
            $this->writer->write(
                $this->exportData(),
            );

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $csv,
        );
    }

    public function test_csv_contains_deterministic_header_order(): void
    {
        $csv =
            $this->writer->write(
                $this->exportData(),
            );

        $withoutBom =
            substr(
                $csv,
                3,
            );

        $firstLine =
            strtok(
                $withoutBom,
                "\r\n",
            );

        $this->assertSame(
            'product_id,product_name,sku,historical,quantity_sold,orders_count,gross_sales',
            $firstLine,
        );
    }

    public function test_csv_contains_utf8_content(): void
    {
        $export =
            new ReportExportData(
                columns: [
                    'product_id',
                    'product_name',
                    'sku',
                    'historical',
                    'quantity_sold',
                    'orders_count',
                    'gross_sales',
                ],

                rows: [
                    new ProductPerformanceExportRowData(
                        productId: 1,

                        productName: 'বাংলা পণ্য',

                        sku: 'BD-001',

                        historical: false,

                        quantitySold: 2,

                        ordersCount: 1,

                        grossSales: 20_000,
                    ),
                ],
            );

        $csv =
            $this->writer->write(
                $export,
            );

        $this->assertStringContainsString(
            'বাংলা পণ্য',
            $csv,
        );
    }

    public function test_formula_like_content_is_neutralized(): void
    {
        $export =
            new ReportExportData(
                columns: [
                    'product_id',
                    'product_name',
                    'sku',
                    'historical',
                    'quantity_sold',
                    'orders_count',
                    'gross_sales',
                ],

                rows: [
                    new ProductPerformanceExportRowData(
                        productId: null,

                        productName: '=SUM(A1:A5)',

                        sku: '@MALICIOUS',

                        historical: true,

                        quantitySold: 1,

                        ordersCount: 1,

                        grossSales: 10_000,
                    ),
                ],
            );

        $csv =
            $this->writer->write(
                $export,
            );

        $this->assertStringContainsString(
            "'=SUM(A1:A5)",
            $csv,
        );

        $this->assertStringContainsString(
            "'@MALICIOUS",
            $csv,
        );
    }

    public function test_integer_money_remains_unformatted_minor_units(): void
    {
        $csv =
            $this->writer->write(
                $this->exportData(),
            );

        $this->assertStringContainsString(
            '3160000',
            $csv,
        );

        $this->assertStringNotContainsString(
            '31,600',
            $csv,
        );
    }

    public function test_csv_quotes_values_that_require_escaping(): void
    {
        $export =
            new ReportExportData(
                columns: [
                    'product_id',
                    'product_name',
                    'sku',
                    'historical',
                    'quantity_sold',
                    'orders_count',
                    'gross_sales',
                ],

                rows: [
                    new ProductPerformanceExportRowData(
                        productId: 1,

                        productName: 'Widget, Large',

                        sku: 'SKU-"001"',

                        historical: false,

                        quantitySold: 1,

                        ordersCount: 1,

                        grossSales: 10_000,
                    ),
                ],
            );

        $csv =
            $this->writer->write(
                $export,
            );

        $this->assertStringContainsString(
            '"Widget, Large"',
            $csv,
        );

        $this->assertStringContainsString(
            '"SKU-""001"""',
            $csv,
        );
    }

    private function exportData(): ReportExportData
    {
        return new ReportExportData(
            columns: [
                'product_id',
                'product_name',
                'sku',
                'historical',
                'quantity_sold',
                'orders_count',
                'gross_sales',
            ],

            rows: [
                new ProductPerformanceExportRowData(
                    productId: 10,

                    productName: 'Product A',

                    sku: 'SKU-A',

                    historical: false,

                    quantitySold: 5,

                    ordersCount: 2,

                    grossSales: 3_160_000,
                ),
            ],
        );
    }
}
