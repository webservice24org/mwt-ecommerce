<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

final class OperationalCsvFormatter
{
    public function bom(): string
    {
        if (
            ! (bool) config(
                'reporting.operational_exports.utf8_bom',
                true,
            )
        ) {
            return '';
        }

        return "\xEF\xBB\xBF";
    }

    public function safe(
        string $value,
    ): string {
        $trimmed =
            ltrim(
                $value,
            );

        if (
            $trimmed === ''
        ) {
            return $value;
        }

        if (
            in_array(
                $trimmed[0],
                [
                    '=',
                    '+',
                    '-',
                    '@',
                ],
                true,
            )
        ) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * @param  array<int, bool|float|int|string|null>  $values
     * @return array<int, bool|float|int|string|null>
     */
    public function safeRow(
        array $values,
    ): array {
        return array_map(
            function (bool|float|int|string|null $value): bool|float|int|string|null {
                if (
                    ! is_string(
                        $value,
                    )
                ) {
                    return $value;
                }

                return $this->safe(
                    $value,
                );
            },
            $values,
        );
    }
}
