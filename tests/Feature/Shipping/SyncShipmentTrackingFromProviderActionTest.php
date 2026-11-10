<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\SyncShipmentTrackingFromProviderAction;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use UnexpectedValueException;

final class SyncShipmentTrackingFromProviderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_safe_tracking_and_provider_status_without_changing_shipment_status(): void
    {
        $provider =
            new FakeTrackingShippingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,

            'submission' => $submission,
        ] = $this->submittedFixture(
            providerCode: $provider->code(),

            shipmentStatus: ShipmentStatus::Shipped,
        );

        $synced = app(
            SyncShipmentTrackingFromProviderAction::class,
        )->execute(
            $shipment,
        );

        $this->assertSame(
            ShipmentProviderTrackingSyncStatus::Synced,
            $synced->tracking_sync_status,
        );

        $this->assertSame(
            1,
            $synced->tracking_sync_attempt_count,
        );

        $this->assertSame(
            'in_transit',
            $synced->provider_status,
        );

        $this->assertNotNull(
            $synced->last_tracking_synced_at,
        );

        $this->assertSame(
            1,
            $provider->refreshCalls,
        );

        $this->assertSame(
            $submission->external_reference,
            $provider->externalReferences[0],
        );

        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shipment->status,
        );

        $this->assertSame(
            'Fake Courier',
            $shipment->carrier,
        );

        $this->assertSame(
            'Express',
            $shipment->service,
        );

        $this->assertSame(
            'TRACK-SYNC-1',
            $shipment->tracking_number,
        );

        $this->assertSame(
            'https://tracking.example.test/SYNC-1',
            $shipment->tracking_url,
        );
    }

    public function test_sync_failure_is_sanitized_and_retry_can_succeed(): void
    {
        $provider =
            new FakeTrackingShippingProvider(
                failuresRemaining: 1,
            );

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->submittedFixture(
            providerCode: $provider->code(),
        );

        $action = app(
            SyncShipmentTrackingFromProviderAction::class,
        );

        try {
            $action->execute(
                $shipment,
            );

            $this->fail(
                'The first tracking synchronization should fail.',
            );
        } catch (
            RuntimeException
        ) {
            //
        }

        $failed =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            ShipmentProviderTrackingSyncStatus::Failed,
            $failed->tracking_sync_status,
        );

        $this->assertSame(
            1,
            $failed->tracking_sync_attempt_count,
        );

        $this->assertSame(
            'Provider tracking synchronization failed.',
            $failed->last_tracking_sync_error,
        );

        $synced = $action->execute(
            $shipment->refresh(),
        );

        $this->assertSame(
            ShipmentProviderTrackingSyncStatus::Synced,
            $synced->tracking_sync_status,
        );

        $this->assertSame(
            2,
            $synced->tracking_sync_attempt_count,
        );

        $this->assertSame(
            2,
            $provider->refreshCalls,
        );
    }

    public function test_terminal_shipment_records_provider_snapshot_but_does_not_rewrite_tracking_fields(): void
    {
        $provider =
            new FakeTrackingShippingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->submittedFixture(
            providerCode: $provider->code(),

            shipmentStatus: ShipmentStatus::Delivered,

            trackingNumber: 'FINAL-TRACKING',
        );

        $synced = app(
            SyncShipmentTrackingFromProviderAction::class,
        )->execute(
            $shipment,
        );

        $this->assertSame(
            'in_transit',
            $synced->provider_status,
        );

        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Delivered,
            $shipment->status,
        );

        $this->assertSame(
            'FINAL-TRACKING',
            $shipment->tracking_number,
        );

        $this->assertNull(
            $shipment->tracking_url,
        );
    }

    public function test_tracking_sync_does_not_perform_provider_status_reconciliation(): void
    {
        $provider =
            new FakeTrackingShippingProvider(
                providerStatus: 'delivered',
            );

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->submittedFixture(
            providerCode: $provider->code(),

            shipmentStatus: ShipmentStatus::Shipped,
        );

        $synced = app(
            SyncShipmentTrackingFromProviderAction::class,
        )->execute(
            $shipment,
        );

        $this->assertSame(
            'delivered',
            $synced->provider_status,
        );

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shipment
                ->refresh()
                ->status,
        );
    }

    public function test_invalid_remote_tracking_url_fails_without_persisting_it(): void
    {
        $provider =
            new FakeTrackingShippingProvider(
                trackingUrl: 'javascript:alert(1)',
            );

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->submittedFixture(
            providerCode: $provider->code(),
        );

        try {
            app(
                SyncShipmentTrackingFromProviderAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'Unsafe provider tracking URL should fail.',
            );
        } catch (
            UnexpectedValueException
        ) {
            //
        }

        $submission =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            ShipmentProviderTrackingSyncStatus::Failed,
            $submission->tracking_sync_status,
        );

        $this->assertNull(
            $shipment
                ->refresh()
                ->tracking_url,
        );
    }

    public function test_tracking_sync_requires_submitted_provider_record(): void
    {
        $provider =
            new FakeTrackingShippingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->submittedFixture(
            providerCode: $provider->code(),

            submissionStatus: ShipmentProviderSubmissionStatus::Failed,
        );

        try {
            app(
                SyncShipmentTrackingFromProviderAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'Failed provider submission must not synchronize tracking.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'provider',
                $exception->errors(),
            );
        }

        $this->assertSame(
            0,
            $provider->refreshCalls,
        );
    }

    /**
     * @return array{
     *     shipment: Shipment,
     *     submission: ShipmentProviderSubmission
     * }
     */
    private function submittedFixture(
        string $providerCode,
        ShipmentStatus $shipmentStatus = ShipmentStatus::Processing,
        ShipmentProviderSubmissionStatus $submissionStatus = ShipmentProviderSubmissionStatus::Submitted,
        ?string $trackingNumber = null,
    ): array {
        $user = User::factory()
            ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => $providerCode,

                    'is_active' => true,
                ]);

        $order = Order::factory()
            ->for(
                $user,
            )
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,

                'shipping_method_id' => $shippingMethod->id,

                'shipping_method_code' => $shippingMethod->code,

                'shipping_method_name' => $shippingMethod->name,
            ]);

        $shipment = Shipment::factory()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'status' => $shipmentStatus,

                'tracking_number' => $trackingNumber,

                'tracking_url' => null,
            ]);

        $submission =
            ShipmentProviderSubmission::query()
                ->create([
                    'shipment_id' => $shipment->id,

                    'provider_code' => $providerCode,

                    'idempotency_key' => '00000000-0000-4000-8000-000000000001',

                    'status' => $submissionStatus,

                    'external_reference' => 'REMOTE-SHIPMENT-1',

                    'attempt_count' => 1,

                    'last_attempted_at' => now(),

                    'submitted_at' => $submissionStatus ===
                        ShipmentProviderSubmissionStatus::Submitted
                            ? now()
                            : null,

                    'last_error' => null,

                    'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Pending,

                    'tracking_sync_attempt_count' => 0,

                    'provider_status' => null,

                    'last_tracking_sync_attempt_at' => null,

                    'last_tracking_synced_at' => null,

                    'last_tracking_sync_error' => null,

                    'metadata' => null,
                ]);

        return [
            'shipment' => $shipment,

            'submission' => $submission,
        ];
    }
}

final class FakeTrackingShippingProvider implements ShippingProvider
{
    public int $refreshCalls = 0;

    /**
     * @var list<string>
     */
    public array $externalReferences = [];

    public function __construct(
        private readonly string $code = 'fake-tracking',
        private int $failuresRemaining = 0,
        private readonly string $providerStatus = 'in_transit',
        private readonly string $trackingUrl = 'https://tracking.example.test/SYNC-1',
    ) {}

    public function code(): string
    {
        return $this->code;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        return new ProviderShipmentSubmissionResult(
            externalReference: 'REMOTE-SHIPMENT-1',
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        $this->refreshCalls++;

        $this->externalReferences[] =
            $externalReference;

        if (
            $this->failuresRemaining >
            0
        ) {
            $this->failuresRemaining--;

            throw new RuntimeException(
                'Provider secret must not be persisted.',
            );
        }

        return new ProviderShipmentTrackingResult(
            providerStatus: $this->providerStatus,

            carrier: 'Fake Courier',

            service: 'Express',

            trackingNumber: 'TRACK-SYNC-1',

            trackingUrl: $this->trackingUrl,

            metadata: [
                'checkpoint_count' => 3,
            ],
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return null;
    }
}
