<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum OperationalExportType: string
{
    case OperationalOrders =
        'operational_orders';

    case CodOperations =
        'cod_operations';

    case InventoryOperations =
        'inventory_operations';

    case ActionQueue =
        'action_queue';

    public function label(): string
    {
        return match ($this) {
            self::OperationalOrders => 'Operational Orders',

            self::CodOperations => 'COD Operations',

            self::InventoryOperations => 'Inventory Operations',

            self::ActionQueue => 'Action Queue',
        };
    }

    public function filenamePrefix(): string
    {
        return match ($this) {
            self::OperationalOrders => 'operational-orders',

            self::CodOperations => 'cod-operations',

            self::InventoryOperations => 'inventory-operations',

            self::ActionQueue => 'admin-action-queue',
        };
    }
}
