<?php

declare(strict_types=1);

namespace Tests\Unit\Shipping;

use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

final class ShippingProviderRegistryTest extends TestCase
{
    public function test_container_registry_contains_manual_provider(): void
    {
        $registry = app(
            ShippingProviderRegistry::class,
        );

        $this->assertSame(
            $registry,
            app(
                ShippingProviderRegistry::class,
            ),
        );

        $this->assertTrue(
            $registry->has(
                'manual',
            ),
        );

        $this->assertContains(
            ManualShippingProvider::CODE,
            $registry->codes(),
        );

        $provider = $registry->resolve(
            ' MANUAL ',
        );

        $this->assertInstanceOf(
            ManualShippingProvider::class,
            $provider,
        );
    }

    public function test_registry_resolves_provider_for_shipping_method_driver(): void
    {
        $shippingMethod = new ShippingMethod([
            'name' => 'Manual Delivery',

            'code' => 'manual-delivery',

            'driver' => 'manual',

            'is_active' => true,
        ]);

        $provider = app(
            ShippingProviderRegistry::class,
        )->resolveFor(
            $shippingMethod,
        );

        $this->assertInstanceOf(
            ManualShippingProvider::class,
            $provider,
        );
    }

    public function test_manual_provider_has_no_external_side_effect(): void
    {
        $shipment = new Shipment([
            'carrier' => 'Existing Carrier',

            'tracking_number' => 'TRACK-1',
        ]);

        $provider =
            new ManualShippingProvider;

        $submissionResult =
            $provider->createShipment(
                shipment: $shipment,

                idempotencyKey: 'test-key',
            );

        $trackingResult =
            $provider->refreshTracking(
                shipment: $shipment,

                externalReference: 'manual-reference',
            );

        $this->assertNull(
            $submissionResult->externalReference,
        );

        $this->assertNull(
            $trackingResult->providerStatus,
        );

        $this->assertSame(
            'Existing Carrier',
            $shipment->carrier,
        );

        $this->assertSame(
            'TRACK-1',
            $shipment->tracking_number,
        );
    }

    public function test_duplicate_provider_code_is_rejected(): void
    {
        $registry =
            new ShippingProviderRegistry;

        $registry->register(
            new ManualShippingProvider,
        );

        $duplicate =
            new class implements ShippingProvider
            {
                public function code(): string
                {
                    return ' MANUAL ';
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
            };

        $this->expectException(
            LogicException::class,
        );

        $registry->register(
            $duplicate,
        );
    }

    public function test_unknown_provider_code_is_rejected(): void
    {
        $registry =
            new ShippingProviderRegistry;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $registry->resolve(
            'missing-provider',
        );
    }
}
