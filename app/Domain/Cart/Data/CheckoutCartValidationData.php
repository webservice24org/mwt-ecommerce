<?php

declare(strict_types=1);

namespace App\Domain\Cart\Data;

final readonly class CheckoutCartValidationData
{
    public function __construct(
        public CartSummaryData $summary,
        public bool $wasSanitized,
    ) {}
}
