<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use InvalidArgumentException;

final class StructuredDataMoneyFormatter
{
    public function fromMinorUnits(
        int $amount,
    ): string {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                'Structured data prices cannot be negative.',
            );
        }

        return sprintf(
            '%d.%02d',
            intdiv(
                $amount,
                100,
            ),
            $amount % 100,
        );
    }
}
