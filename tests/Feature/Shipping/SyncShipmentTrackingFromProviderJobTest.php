<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\ReconcileShipmentProviderStatusAction;
use App\Domain\Shipping\Actions\SyncShipmentTrackingFromProviderAction;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Jobs\Shipping\SyncShipmentTrackingFromProviderJob;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncShipmentTrackingFromProviderJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_unique_and_queued_on_shipping_queue_with_retry_policy(): void
    {
        $job =
            new SyncShipmentTrackingFromProviderJob(
                shipmentId: 123,
            );

        $this->assertInstanceOf(
            ShouldQueue::class,
            $job,
        );

        $this->assertInstanceOf(
            ShouldBeUnique::class,
            $job,
        );

        $this->assertSame(
            '123',
            $job->uniqueId(),
        );

        $this->assertSame(
            300,
            $job->uniqueFor,
        );

        $this->assertSame(
            5,
            $job->tries,
        );

        $this->assertSame(
            30,
            $job->timeout,
        );

        $this->assertSame(
            [
                60,
                300,
                900,
                1800,
            ],
            $job->backoff(),
        );

        $this->assertSame(
            'shipping',
            $job->queue,
        );
    }

    public function test_job_executes_tracking_sync_and_reconciliation(): void
    {
        $provider =
            new FakeQueuedTrackingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        $user = User::factory()
            ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => $provider->code(),

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
            ->create();

        ShipmentProviderSubmission::query()
            ->create([
                'shipment_id' => $shipment->id,

                'provider_code' => $provider->code(),

                'idempotency_key' => '00000000-0000-4000-8000-000000000002',

                'status' => ShipmentProviderSubmissionStatus::Submitted,

                'external_reference' => 'REMOTE-JOB-1',

                'attempt_count' => 1,

                'last_attempted_at' => now(),

                'submitted_at' => now(),

                'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Pending,

                'tracking_sync_attempt_count' => 0,
            ]);

        $job =
            new SyncShipmentTrackingFromProviderJob(
                shipmentId: $shipment->id,
            );

        $job->handle(
            app(
                SyncShipmentTrackingFromProviderAction::class,
            ),
            app(
                ReconcileShipmentProviderStatusAction::class,
            ),
        );

        $submission =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            ShipmentProviderTrackingSyncStatus::Synced,
            $submission->tracking_sync_status,
        );

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Unmapped,
            $submission->reconciliation_status,
        );

        $this->assertSame(
            1,
            $provider->refreshCalls,
        );

        $this->assertSame(
            'TRACK-JOB-1',
            $shipment
                ->refresh()
                ->tracking_number,
        );
    }

    public function test_job_safely_ignores_missing_shipment(): void
    {
        $job =
            new SyncShipmentTrackingFromProviderJob(
                shipmentId: 999999,
            );

        $job->handle(
            app(
                SyncShipmentTrackingFromProviderAction::class,
            ),
            app(
                ReconcileShipmentProviderStatusAction::class,
            ),
        );

        $this->assertTrue(
            true,
        );
    }
}

final class FakeQueuedTrackingProvider implements ShippingProvider
{
    public int $refreshCalls = 0;

    public function code(): string
    {
        return 'fake-queued';
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        return new ProviderShipmentSubmissionResult(
            externalReference: 'REMOTE-JOB-1',
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        $this->refreshCalls++;

        return new ProviderShipmentTrackingResult(
            providerStatus: 'in_transit',

            trackingNumber: 'TRACK-JOB-1',
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return null;
    }
}
