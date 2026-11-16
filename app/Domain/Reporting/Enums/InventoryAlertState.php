<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum InventoryAlertState: string
{
    case NotTracked =
        'not_tracked';

    case Healthy =
        'healthy';

    case LowStock =
        'low_stock';

    case CriticalStock =
        'critical_stock';

    case OutOfStock =
        'out_of_stock';

    case NegativeStock =
        'negative_stock';

    public function label(): string
    {
        return match ($this) {
            self::NotTracked => 'Inventory not tracked',

            self::Healthy => 'Healthy stock',

            self::LowStock => 'Low stock',

            self::CriticalStock => 'Critical stock',

            self::OutOfStock => 'Out of stock',

            self::NegativeStock => 'Negative stock',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::NotTracked,
            self::Healthy => 'normal',

            self::LowStock => 'warning',

            self::CriticalStock => 'high',

            self::OutOfStock,
            self::NegativeStock => 'critical',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::NotTracked => 0,

            self::Healthy => 10,

            self::LowStock => 30,

            self::CriticalStock => 50,

            self::OutOfStock => 70,

            self::NegativeStock => 90,
        };
    }
}
