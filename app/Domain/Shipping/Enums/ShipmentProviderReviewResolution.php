<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShipmentProviderReviewResolution: string
{
    case ConfirmExisting = 'confirm_existing';
    case AllowRetry = 'allow_retry';

    public function label(): string
    {
        return match ($this) {
            self::ConfirmExisting => 'Confirm existing provider shipment',
            self::AllowRetry => 'Confirm safe to retry',
        };
    }
}
