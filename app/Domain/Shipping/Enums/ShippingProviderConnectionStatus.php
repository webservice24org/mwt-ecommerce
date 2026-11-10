<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShippingProviderConnectionStatus: string
{
    case Disconnected =
        'disconnected';

    case Configured =
        'configured';

    case Connected =
        'connected';

    case Error =
        'error';

    public function label(): string
    {
        return match ($this) {
            self::Disconnected => 'Disconnected',

            self::Configured => 'Configured',

            self::Connected => 'Connected',

            self::Error => 'Connection Error',
        };
    }
}
