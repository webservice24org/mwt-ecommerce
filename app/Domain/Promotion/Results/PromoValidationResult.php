<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Results;

final readonly class PromoValidationResult
{
    private function __construct(
        public bool $eligible,
        public ?string $reason,
    ) {}

    public static function eligible(): self
    {
        return new self(
            eligible: true,
            reason: null,
        );
    }

    public static function rejected(
        string $reason,
    ): self {
        return new self(
            eligible: false,
            reason: $reason,
        );
    }
}
