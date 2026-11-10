<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\ReconcileShipmentProviderStatusAction;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class ReconcileShipmentProviderStatusActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_pending_shipment_can_catch_up_to_shipped_through_existing_actions(): void
    {
        $provider = new FakeReconciliationShippingProvider([
            'remote_shipped' => ShipmentStatus::Shipped,
        ]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'order' => $order,
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'remote_shipped',
            shipmentStatus: ShipmentStatus::Pending,
            fulfilmentStatus: FulfilmentStatus::Processing,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Applied,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Shipped,
            $result->provider_mapped_shipment_status,
        );

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shipment->refresh()->status,
        );

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order->refresh()->fulfilment_status,
        );
    }

    public function test_processing_shipment_can_catch_up_to_delivered_through_existing_actions(): void
    {
        $provider = new FakeReconciliationShippingProvider([
            'remote_delivered' => ShipmentStatus::Delivered,
        ]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'order' => $order,
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'remote_delivered',
            shipmentStatus: ShipmentStatus::Processing,
            fulfilmentStatus: FulfilmentStatus::Processing,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Applied,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Delivered,
            $shipment->refresh()->status,
        );

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order->refresh()->fulfilment_status,
        );
    }

    public function test_shipped_shipment_can_reconcile_to_returned(): void
    {
        $provider = new FakeReconciliationShippingProvider([
            'remote_returned' => ShipmentStatus::Returned,
        ]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'order' => $order,
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'remote_returned',
            shipmentStatus: ShipmentStatus::Shipped,
            fulfilmentStatus: FulfilmentStatus::Shipped,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Applied,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Returned,
            $shipment->refresh()->status,
        );

        $this->assertSame(
            FulfilmentStatus::Returned,
            $order->refresh()->fulfilment_status,
        );
    }

    public function test_unmapped_provider_status_is_recorded_without_state_change(): void
    {
        $provider = new FakeReconciliationShippingProvider([]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'courier_custom_unknown',
            shipmentStatus: ShipmentStatus::Processing,
            fulfilmentStatus: FulfilmentStatus::Processing,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Unmapped,
            $result->reconciliation_status,
        );

        $this->assertNull(
            $result->provider_mapped_shipment_status,
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment->refresh()->status,
        );
    }

    public function test_provider_regression_never_moves_internal_shipment_backward(): void
    {
        $provider = new FakeReconciliationShippingProvider([
            'remote_shipped' => ShipmentStatus::Shipped,
        ]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'remote_shipped',
            shipmentStatus: ShipmentStatus::Delivered,
            fulfilmentStatus: FulfilmentStatus::Delivered,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::NoChange,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Delivered,
            $shipment->refresh()->status,
        );
    }

    public function test_provider_cancelled_requires_manual_review_and_does_not_release_allocation(): void
    {
        $provider = new FakeReconciliationShippingProvider([
            'remote_cancelled' => ShipmentStatus::Cancelled,
        ]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'remote_cancelled',
            shipmentStatus: ShipmentStatus::Processing,
            fulfilmentStatus: FulfilmentStatus::Processing,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Blocked,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Cancelled,
            $result->provider_mapped_shipment_status,
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment->refresh()->status,
        );

        $this->assertDatabaseHas(
            'shipment_items',
            [
                'shipment_id' => $shipment->id,
                'quantity' => 1,
            ],
        );
    }

    public function test_provider_state_is_blocked_when_order_state_cannot_perform_required_transition(): void
    {
        $provider = new FakeReconciliationShippingProvider([
            'remote_shipped' => ShipmentStatus::Shipped,
        ]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'remote_shipped',
            shipmentStatus: ShipmentStatus::Pending,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            orderStatus: OrderStatus::Pending,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Blocked,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Pending,
            $shipment->refresh()->status,
        );

        $this->assertSame(
            1,
            $result->reconciliation_attempt_count,
        );

        $this->assertNotNull(
            $result->last_reconciliation_error,
        );
    }

    public function test_same_mapped_status_is_idempotent_no_change(): void
    {
        $provider = new FakeReconciliationShippingProvider([
            'remote_shipped' => ShipmentStatus::Shipped,
        ]);

        app(ShippingProviderRegistry::class)->register($provider);

        [
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture(
            providerCode: $provider->code(),
            providerStatus: 'remote_shipped',
            shipmentStatus: ShipmentStatus::Shipped,
            fulfilmentStatus: FulfilmentStatus::Shipped,
        );

        $result = app(
            ReconcileShipmentProviderStatusAction::class,
        )->execute($submission);

        $this->assertSame(
            ShipmentProviderReconciliationStatus::NoChange,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shipment->refresh()->status,
        );
    }

    /**
     * @return array{
     *     order: Order,
     *     shipment: Shipment,
     *     submission: ShipmentProviderSubmission
     * }
     */
    private function fixture(
        string $providerCode,
        string $providerStatus,
        ShipmentStatus $shipmentStatus,
        FulfilmentStatus $fulfilmentStatus,
        OrderStatus $orderStatus = OrderStatus::Processing,
    ): array {
        $user = User::factory()->create();

        $shippingMethod = ShippingMethod::factory()
            ->create([
                'driver' => $providerCode,
                'is_active' => true,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => $orderStatus,
                'fulfilment_status' => $fulfilmentStatus,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_method_code' => $shippingMethod->code,
                'shipping_method_name' => $shippingMethod->name,
            ]);

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 1,
            ]);

        $shipment = Shipment::factory()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'status' => $shipmentStatus,
            ]);

        ShipmentItem::factory()
            ->for($shipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 1,
            ]);

        $submission = ShipmentProviderSubmission::query()
            ->create([
                'shipment_id' => $shipment->id,
                'provider_code' => $providerCode,
                'idempotency_key' => '00000000-0000-4000-8000-000000000003',
                'status' => ShipmentProviderSubmissionStatus::Submitted,
                'external_reference' => 'REMOTE-RECONCILE-1',
                'attempt_count' => 1,
                'last_attempted_at' => now(),
                'submitted_at' => now(),
                'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Synced,
                'tracking_sync_attempt_count' => 1,
                'provider_status' => $providerStatus,
                'provider_mapped_shipment_status' => null,
                'reconciliation_status' => ShipmentProviderReconciliationStatus::Pending,
                'reconciliation_attempt_count' => 0,
            ]);

        return [
            'order' => $order,
            'shipment' => $shipment,
            'submission' => $submission,
        ];
    }
}

final class FakeReconciliationShippingProvider implements ShippingProvider
{
    /**
     * @param  array<string, ShipmentStatus>  $map
     */
    public function __construct(
        private readonly array $map,
    ) {}

    public function code(): string
    {
        return 'fake-reconcile';
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        return new ProviderShipmentSubmissionResult(
            externalReference: 'REMOTE-RECONCILE-1',
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
        if ($providerStatus === null) {
            return null;
        }

        return $this->map[$providerStatus] ?? null;
    }
}
