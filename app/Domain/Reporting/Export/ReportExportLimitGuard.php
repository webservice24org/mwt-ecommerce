<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Export;

use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class ReportExportLimitGuard
{
    public function maxRows(): int
    {
        $configured =
            config(
                'reporting.exports.max_rows',
                10_000,
            );

        if (
            ! is_numeric(
                $configured,
            )
        ) {
            return 10_000;
        }

        return max(
            1,
            (int) $configured,
        );
    }

    public function assertAllowed(
        int $rowCount,
    ): void {
        if (
            $rowCount <=
            $this->maxRows()
        ) {
            return;
        }

        throw new HttpException(
            413,
            sprintf(
                'This export contains %s rows and exceeds the maximum allowed export size of %s rows. Narrow the report filters and try again.',
                number_format(
                    $rowCount,
                ),
                number_format(
                    $this->maxRows(),
                ),
            ),
        );
    }
}
