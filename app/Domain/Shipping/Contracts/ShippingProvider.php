<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Shipment;

interface ShippingProvider
{
    public function code(): string;

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult;

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult;

    /**
     * Translate a provider-specific remote status into the
     * internal physical-shipment state it safely implies.
     *
     * Return null when the status is unknown, informational,
     * ambiguous, or requires manual review.
     */
    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus;
}
