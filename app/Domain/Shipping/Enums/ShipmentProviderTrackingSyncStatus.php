<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShipmentProviderTrackingSyncStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Syncing = 'syncing';
    case Synced = 'synced';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Queued => 'Queued',
            self::Syncing => 'Syncing',
            self::Synced => 'Synced',
            self::Failed => 'Failed',
        };
    }
}
