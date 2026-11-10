<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShippingProviderCode: string
{
    case Manual = 'manual';

    case Pathao = 'pathao';

    case Steadfast = 'steadfast';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',

            self::Pathao => 'Pathao',

            self::Steadfast => 'Steadfast',
        };
    }

    public function isExternal(): bool
    {
        return $this !==
            self::Manual;
    }

    public function requiresAccount(): bool
    {
        return $this !==
            self::Manual;
    }

    public function supportsSubmission(): bool
    {
        return match ($this) {
            self::Manual => false,

            self::Pathao,
            self::Steadfast => true,
        };
    }

    public function supportsTrackingSync(): bool
    {
        return match ($this) {
            self::Manual => false,

            self::Pathao,
            self::Steadfast => true,
        };
    }
}
