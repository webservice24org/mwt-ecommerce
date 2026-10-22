<?php

declare(strict_types=1);

namespace App\Domain\Orders\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;

final class InvoiceNumberGenerator
{
    public function generate(): string
    {
        do {
            $number = sprintf(
                'INV-%s-%s',
                now()->format(
                    'Ymd',
                ),
                Str::upper(
                    Str::random(
                        8,
                    ),
                ),
            );
        } while (
            Invoice::query()
                ->where(
                    'invoice_number',
                    $number,
                )
                ->exists()
        );

        return $number;
    }
}
