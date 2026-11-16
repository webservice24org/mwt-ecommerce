<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum ActionQueueSource: string
{
    case OperationalOrders =
        'operational_orders';

    case CodOperations =
        'cod_operations';

    case InventoryOperations =
        'inventory_operations';

    public function label(): string
    {
        return match ($this) {
            self::OperationalOrders => 'Operational Orders',

            self::CodOperations => 'COD Operations',

            self::InventoryOperations => 'Inventory Operations',
        };
    }
}
