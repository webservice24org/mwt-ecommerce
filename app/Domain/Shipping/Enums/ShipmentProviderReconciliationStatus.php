<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShipmentProviderReconciliationStatus: string
{
    case Pending = 'pending';
    case Reconciling = 'reconciling';
    case Applied = 'applied';
    case NoChange = 'no_change';
    case Unmapped = 'unmapped';
    case Blocked = 'blocked';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Reconciling => 'Reconciling',
            self::Applied => 'Applied',
            self::NoChange => 'No change',
            self::Unmapped => 'Unmapped',
            self::Blocked => 'Blocked',
            self::Failed => 'Failed',
        };
    }
}
