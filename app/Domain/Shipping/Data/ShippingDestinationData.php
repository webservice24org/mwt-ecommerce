<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Data;

final readonly class ShippingDestinationData
{
    public function __construct(
        public string $countryCode,
        public ?string $stateOrRegion = null,
        public ?string $city = null,
        public ?string $postalCode = null,
    ) {}
}
