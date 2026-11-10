<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Providers;

use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Shipment;

final readonly class ManualShippingProvider implements ShippingProvider
{
    public const CODE = 'manual';

    public function code(): string
    {
        return self::CODE;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        return new ProviderShipmentSubmissionResult(
            externalReference: null,
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        return new ProviderShipmentTrackingResult(
            providerStatus: null,
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return null;
    }
}
