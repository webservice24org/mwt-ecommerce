<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Data\ShippingDestinationData;
use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;

final readonly class ShippingZoneMatcher
{
    public function __construct(
        private ShippingZoneRuleNormalizer $normalizer,
    ) {}

    public function matches(
        ShippingZone $zone,
        ShippingDestinationData $destination,
    ): bool {
        if (! $zone->is_active) {
            return false;
        }

        if ($zone->is_catch_all) {
            return true;
        }

        $rules = $zone->relationLoaded('rules')
            ? $zone->rules
            : $zone->rules()->get();

        if ($rules->isEmpty()) {
            return false;
        }

        foreach (ShippingZoneRuleType::cases() as $type) {
            $rulesForType = $rules->filter(
                static fn (
                    ShippingZoneRule $rule,
                ): bool => $rule->ruleType() === $type,
            );

            if ($rulesForType->isEmpty()) {
                continue;
            }

            $destinationValue =
                $this->destinationValue(
                    $type,
                    $destination,
                );

            if ($destinationValue === null) {
                return false;
            }

            $normalizedDestination =
                $this->normalizer->normalize(
                    $type,
                    $destinationValue,
                );

            $matchesType = $rulesForType->contains(
                fn (
                    ShippingZoneRule $rule,
                ): bool => $this->normalizer->normalize(
                    $type,
                    $rule->value,
                ) === $normalizedDestination,
            );

            if (! $matchesType) {
                return false;
            }
        }

        return true;
    }

    private function destinationValue(
        ShippingZoneRuleType $type,
        ShippingDestinationData $destination,
    ): ?string {
        return match ($type) {
            ShippingZoneRuleType::CountryCode => $destination->countryCode,

            ShippingZoneRuleType::StateOrRegion => $destination->stateOrRegion,

            ShippingZoneRuleType::City => $destination->city,

            ShippingZoneRuleType::PostalCode => $destination->postalCode,
        };
    }
}
