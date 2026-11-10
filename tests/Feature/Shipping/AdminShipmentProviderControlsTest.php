<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Queries\GetAdminOrderShippingReadModelQuery;
use App\Jobs\Shipping\SyncShipmentTrackingFromProviderJob;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class AdminShipmentProviderControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_provider_without_submission_is_exposed_as_submittable(): void
    {
        $provider =
            new AdminControlsFakeProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'order' => $order,

            'shipment' => $shipment,
        ] = $this->fixture(
            driver: $provider->code(),
        );

        $shipping = app(
            GetAdminOrderShippingReadModelQuery::class,
        )->get(
            $order,
        );

        $row = $shipping['shipments'][0];

        $this->assertSame(
            $shipment->id,
            $row['id'],
        );

        $this->assertTrue(
            $row['provider']['registered'],
        );

        $this->assertTrue(
            $row['provider']['external'],
        );

        $this->assertNull(
            $row['provider']['submission'],
        );

        $this->assertTrue(
            $row['actions']['can_submit_provider'],
        );

        $this->assertFalse(
            $row['actions']['can_sync_provider'],
        );
    }

    public function test_manual_provider_has_no_external_controls(): void
    {
        [
            'order' => $order,
        ] = $this->fixture(
            driver: 'manual',
        );

        $shipping = app(
            GetAdminOrderShippingReadModelQuery::class,
        )->get(
            $order,
        );

        $row = $shipping['shipments'][0];

        $this->assertFalse(
            $row['provider']['external'],
        );

        $this->assertFalse(
            $row['actions']['can_submit_provider'],
        );

        $this->assertFalse(
            $row['actions']['can_sync_provider'],
        );
    }

    public function test_admin_can_submit_shipment_to_registered_provider(): void
    {
        $provider =
            new AdminControlsFakeProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'admin' => $admin,

            'order' => $order,

            'shipment' => $shipment,
        ] = $this->fixture(
            driver: $provider->code(),
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.provider.submit',
                    [
                        $order,
                        $shipment,
                    ],
                ),
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
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
            ShipmentProviderSubmissionStatus::Submitted,
            $submission->status,
        );

        $this->assertSame(
            'ADMIN-REMOTE-1',
            $submission->external_reference,
        );
    }

    public function test_submitted_provider_is_exposed_as_syncable(): void
    {
        $provider =
            new AdminControlsFakeProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'order' => $order,

            'shipment' => $shipment,
        ] = $this->fixture(
            driver: $provider->code(),
        );

        $submission =
            $this->submitted(
                shipment: $shipment,

                providerCode: $provider->code(),
            );

        $shipping = app(
            GetAdminOrderShippingReadModelQuery::class,
        )->get(
            $order,
        );

        $row = $shipping['shipments'][0];

        $this->assertFalse(
            $row['actions']['can_submit_provider'],
        );

        $this->assertTrue(
            $row['actions']['can_sync_provider'],
        );

        $this->assertSame(
            $submission->external_reference,
            $row['provider']['submission']['external_reference'],
        );

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Pending->value,
            $row['provider']['submission']['reconciliation_status']['value'],
        );
    }

    public function test_admin_sync_control_queues_unique_tracking_job_and_marks_submission_queued(): void
    {
        Bus::fake();

        $provider =
            new AdminControlsFakeProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'admin' => $admin,

            'order' => $order,

            'shipment' => $shipment,
        ] = $this->fixture(
            driver: $provider->code(),
        );

        $this->submitted(
            shipment: $shipment,

            providerCode: $provider->code(),
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.provider.sync',
                    [
                        $order,
                        $shipment,
                    ],
                ),
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
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
            ShipmentProviderTrackingSyncStatus::Queued,
            $submission->tracking_sync_status,
        );

        Bus::assertDispatched(
            SyncShipmentTrackingFromProviderJob::class,
            static fn (
                SyncShipmentTrackingFromProviderJob $job,
            ): bool => $job->shipmentId ===
                $shipment->id,
        );
    }

    public function test_queued_sync_cannot_be_queued_again(): void
    {
        Bus::fake();

        $provider =
            new AdminControlsFakeProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'admin' => $admin,

            'order' => $order,

            'shipment' => $shipment,
        ] = $this->fixture(
            driver: $provider->code(),
        );

        $submission =
            $this->submitted(
                shipment: $shipment,

                providerCode: $provider->code(),
            );

        $submission->update([
            'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Queued,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.orders.show',
                    $order,
                ),
            )
            ->post(
                route(
                    'admin.orders.shipments.provider.sync',
                    [
                        $order,
                        $shipment,
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'provider',
            ]);

        Bus::assertNothingDispatched();
    }

    public function test_provider_routes_reject_cross_order_shipment(): void
    {
        $provider =
            new AdminControlsFakeProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'admin' => $admin,

            'order' => $order,
        ] = $this->fixture(
            driver: $provider->code(),
        );

        [
            'shipment' => $otherShipment,
        ] = $this->fixture(
            driver: $provider->code(),
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.provider.submit',
                    [
                        $order,
                        $otherShipment,
                    ],
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'shipment_provider_submissions',
            [
                'shipment_id' => $otherShipment->id,
            ],
        );
    }

    /**
     * @return array{
     *     admin: Admin,
     *     order: Order,
     *     shipment: Shipment,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function fixture(
        string $driver,
    ): array {
        $admin = Admin::factory()
            ->create();

        $user = User::factory()
            ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => $driver,

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
                'status' => ShipmentStatus::Processing,
            ]);

        return [
            'admin' => $admin,

            'order' => $order,

            'shipment' => $shipment,

            'shippingMethod' => $shippingMethod,
        ];
    }

    private function submitted(
        Shipment $shipment,
        string $providerCode,
    ): ShipmentProviderSubmission {
        return ShipmentProviderSubmission::query()
            ->create([
                'shipment_id' => $shipment->id,

                'provider_code' => $providerCode,

                'idempotency_key' => '00000000-0000-4000-8000-000000000005',

                'status' => ShipmentProviderSubmissionStatus::Submitted,

                'external_reference' => 'ADMIN-REMOTE-1',

                'attempt_count' => 1,

                'last_attempted_at' => now(),

                'submitted_at' => now(),

                'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Pending,

                'tracking_sync_attempt_count' => 0,

                'provider_status' => null,

                'provider_mapped_shipment_status' => null,

                'reconciliation_status' => ShipmentProviderReconciliationStatus::Pending,

                'reconciliation_attempt_count' => 0,
            ]);
    }
}

final class AdminControlsFakeProvider implements ShippingProvider
{
    public function code(): string
    {
        return 'admin-fake';
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        return new ProviderShipmentSubmissionResult(
            externalReference: 'ADMIN-REMOTE-1',

            carrier: 'Admin Fake Courier',

            trackingNumber: 'ADMIN-TRACK-1',

            trackingUrl: 'https://tracking.example.test/ADMIN-TRACK-1',
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        return new ProviderShipmentTrackingResult(
            providerStatus: 'in_transit',
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return match ($providerStatus) {
            'in_transit' => ShipmentStatus::Shipped,

            default => null,
        };
    }
}
