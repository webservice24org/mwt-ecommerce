<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Data\ShippingDestinationData;
use App\Models\ShippingZone;

final readonly class ShippingZoneResolver
{
    public function __construct(
        private ShippingZoneMatcher $matcher,
    ) {}

    public function resolve(
        ShippingDestinationData $destination,
    ): ?ShippingZone {
        $zones = ShippingZone::query()
            ->with('rules')
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($zones as $zone) {
            if (
                $this->matcher->matches(
                    $zone,
                    $destination,
                )
            ) {
                return $zone;
            }
        }

        return null;
    }
}
