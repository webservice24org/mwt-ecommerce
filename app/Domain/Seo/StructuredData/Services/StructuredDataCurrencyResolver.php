<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use LogicException;

final class StructuredDataCurrencyResolver
{
    public function currency(): string
    {
        $currency =
            strtoupper(
                trim(
                    (string) config(
                        'commerce.currency',
                        'BDT',
                    ),
                ),
            );

        if (
            preg_match(
                '/^[A-Z]{3}$/',
                $currency,
            ) !== 1
        ) {
            throw new LogicException(
                'Structured data currency must be a three-letter currency code.',
            );
        }

        return $currency;
    }
}
