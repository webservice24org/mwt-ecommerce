<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShippingZoneRuleType: string
{
    case CountryCode = 'country_code';
    case StateOrRegion = 'state_or_region';
    case City = 'city';
    case PostalCode = 'postal_code';

    public function label(): string
    {
        return match ($this) {
            self::CountryCode => 'Country',
            self::StateOrRegion => 'State / Region',
            self::City => 'City',
            self::PostalCode => 'Postal Code',
        };
    }
}
