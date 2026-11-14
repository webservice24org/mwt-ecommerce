<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

final readonly class SpreadsheetSafeValueSerializer
{
    public function serialize(
        int|string|null $value,
    ): string {
        if (
            $value === null
        ) {
            return '';
        }

        if (
            is_int(
                $value,
            )
        ) {
            return (string) $value;
        }

        /*
         * Prevent spreadsheet formula
         * execution when CSV is opened in
         * Excel, LibreOffice, Google Sheets,
         * etc.
         */
        if (
            $this->startsLikeFormula(
                $value,
            )
        ) {
            return "'".$value;
        }

        return $value;
    }

    private function startsLikeFormula(
        string $value,
    ): bool {
        if (
            $value === ''
        ) {
            return false;
        }

        $trimmed =
            ltrim(
                $value,
            );

        if (
            $trimmed === ''
        ) {
            return false;
        }

        return in_array(
            $trimmed[0],
            [
                '=',
                '+',
                '-',
                '@',
            ],
            true,
        );
    }
}
