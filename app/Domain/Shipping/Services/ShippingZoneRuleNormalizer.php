<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Enums\ShippingZoneRuleType;

final readonly class ShippingZoneRuleNormalizer
{
    public function normalize(
        ShippingZoneRuleType $type,
        string $value,
    ): string {
        $value = trim($value);

        return match ($type) {
            ShippingZoneRuleType::CountryCode => mb_strtoupper($value),

            ShippingZoneRuleType::PostalCode => mb_strtoupper(
                preg_replace(
                    '/\s+/',
                    '',
                    $value,
                ) ?? $value,
            ),

            ShippingZoneRuleType::StateOrRegion,
            ShippingZoneRuleType::City => mb_strtolower(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $value,
                ) ?? $value,
            ),
        };
    }
}
