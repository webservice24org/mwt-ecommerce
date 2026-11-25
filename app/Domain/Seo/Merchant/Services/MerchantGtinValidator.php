<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

final readonly class MerchantGtinValidator
{
    public function valid(
        ?string $gtin,
    ): bool {
        if ($gtin === null) {
            return false;
        }

        $gtin =
            preg_replace(
                '/\s+/',
                '',
                $gtin,
            );

        if (
            ! is_string(
                $gtin,
            )
            || preg_match(
                '/^\d+$/',
                $gtin,
            ) !== 1
            || ! in_array(
                strlen(
                    $gtin,
                ),
                [
                    8,
                    12,
                    13,
                    14,
                ],
                true,
            )
        ) {
            return false;
        }

        $digits =
            array_map(
                static fn (
                    string $digit,
                ): int => (int) $digit,
                str_split(
                    $gtin,
                ),
            );

        $checkDigit =
            array_pop(
                $digits,
            );

        $sum = 0;
        $weight = 3;

        for (
            $index =
                count(
                    $digits,
                )
                - 1;
            $index >= 0;
            $index--
        ) {
            $sum +=
                $digits[
                    $index
                ]
                * $weight;

            $weight =
                $weight === 3
                    ? 1
                    : 3;
        }

        $expected =
            (
                10
                - (
                    $sum
                    % 10
                )
            )
            % 10;

        return $expected ===
            $checkDigit;
    }
}
