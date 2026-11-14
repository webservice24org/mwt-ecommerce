<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Export;

use App\Domain\Reporting\Contracts\ReportExportWriter;
use App\Domain\Reporting\Enums\ReportExportFormat;
use InvalidArgumentException;

final readonly class ReportExportWriterRegistry
{
    /**
     * @param  iterable<ReportExportWriter>  $writers
     */
    public function __construct(
        private iterable $writers,
    ) {}

    public function forFormat(
        ReportExportFormat $format,
    ): ReportExportWriter {
        $writersByFormat = [];

        foreach (
            $this->writers as $writer
        ) {
            $writersByFormat[
                $writer
                    ->format()
                    ->value
            ] = $writer;
        }

        $writer =
            $writersByFormat[
                $format->value
            ] ?? null;

        if (
            $writer instanceof ReportExportWriter
        ) {
            return $writer;
        }

        throw new InvalidArgumentException(
            sprintf(
                'No report export writer registered for format [%s].',
                $format->value,
            ),
        );
    }
}
