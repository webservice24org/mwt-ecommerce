<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Data;

use Carbon\CarbonImmutable;

final readonly class PromoValidationContext
{
    public function __construct(
        public int $subtotal,
        public ?int $userId = null,
        public ?CarbonImmutable $now = null,
    ) {}
}
