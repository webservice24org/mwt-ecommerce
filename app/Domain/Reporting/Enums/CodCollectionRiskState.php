<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum CodCollectionRiskState: string
{
    case None = 'none';

    case UnpaidRecent =
        'unpaid_recent';

    case UnpaidStale =
        'unpaid_stale';

    case DueRecent =
        'due_recent';

    case DueOverdue =
        'due_overdue';

    case DeliveredUnpaid =
        'delivered_unpaid';

    case DeliveredDue =
        'delivered_due';

    case DeliveredOverdue =
        'delivered_overdue';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No collection risk',

            self::UnpaidRecent => 'Recently unpaid',

            self::UnpaidStale => 'Unpaid too long',

            self::DueRecent => 'Payment due',

            self::DueOverdue => 'Payment overdue',

            self::DeliveredUnpaid => 'Delivered but unpaid',

            self::DeliveredDue => 'Delivered with payment due',

            self::DeliveredOverdue => 'Delivered payment overdue',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::None => 'normal',

            self::UnpaidRecent => 'info',

            self::DueRecent => 'warning',

            self::UnpaidStale,
            self::DeliveredUnpaid => 'high',

            self::DueOverdue,
            self::DeliveredDue,
            self::DeliveredOverdue => 'critical',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::None => 0,

            self::UnpaidRecent => 10,

            self::DueRecent => 20,

            self::UnpaidStale => 30,

            self::DeliveredUnpaid => 40,

            self::DeliveredDue => 50,

            self::DueOverdue => 60,

            self::DeliveredOverdue => 70,
        };
    }
}
