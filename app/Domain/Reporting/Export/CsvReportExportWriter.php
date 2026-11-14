<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Export;

use App\Domain\Reporting\Contracts\ReportExportWriter;
use App\Domain\Reporting\Data\ReportExportData;
use App\Domain\Reporting\Enums\ReportExportFormat;
use App\Domain\Reporting\Support\SpreadsheetSafeValueSerializer;
use RuntimeException;

final readonly class CsvReportExportWriter implements ReportExportWriter
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    public function __construct(
        private SpreadsheetSafeValueSerializer $serializer,
    ) {}

    public function format(): ReportExportFormat
    {
        return ReportExportFormat::Csv;
    }

    public function write(
        ReportExportData $export,
    ): string {
        $stream =
            fopen(
                'php://temp',
                'w+b',
            );

        if (
            $stream === false
        ) {
            throw new RuntimeException(
                'Unable to create CSV export stream.',
            );
        }

        try {
            $this->writeBom(
                $stream,
            );

            $this->writeRow(
                $stream,
                $export->columns,
            );

            foreach (
                $export->rowsAsArrays() as $row
            ) {
                $values = [];

                foreach (
                    $export->columns as $column
                ) {
                    $values[] =
                        $this
                            ->serializer
                            ->serialize(
                                $row[
                                    $column
                                ] ?? null,
                            );
                }

                $this->writeRow(
                    $stream,
                    $values,
                );
            }

            rewind(
                $stream,
            );

            $contents =
                stream_get_contents(
                    $stream,
                );

            if (
                $contents === false
            ) {
                throw new RuntimeException(
                    'Unable to read CSV export stream.',
                );
            }

            return $contents;
        } finally {
            fclose(
                $stream,
            );
        }
    }

    /**
     * @param  resource  $stream
     */
    private function writeBom(
        $stream,
    ): void {
        $written =
            fwrite(
                $stream,
                self::UTF8_BOM,
            );

        if (
            $written === false
        ) {
            throw new RuntimeException(
                'Unable to write UTF-8 BOM.',
            );
        }
    }

    /**
     * @param  resource  $stream
     * @param  list<string>  $values
     */
    private function writeRow(
        $stream,
        array $values,
    ): void {
        $result =
            fputcsv(
                $stream,
                $values,
                ',',
                '"',
                '\\',
            );

        if (
            $result === false
        ) {
            throw new RuntimeException(
                'Unable to write CSV row.',
            );
        }
    }
}
