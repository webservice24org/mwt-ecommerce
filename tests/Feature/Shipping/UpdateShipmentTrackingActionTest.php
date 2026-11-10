<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\UpdateShipmentTrackingAction;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class UpdateShipmentTrackingActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_shipment_tracking_can_be_updated(): void
    {
        [
            'shipment' => $shipment,
            'admin' => $admin,
        ] = $this->shipmentFixture(
            ShipmentStatus::Pending,
        );

        $updated = app(
            UpdateShipmentTrackingAction::class,
        )->execute(
            shipment: $shipment,

            carrier: 'Pathao',

            service: 'Standard',

            trackingNumber: 'MWT-10001',

            trackingUrl: 'https://tracking.example.test/MWT-10001',

            actor: $admin,
        );

        $this->assertSame(
            'Pathao',
            $updated->carrier,
        );

        $this->assertSame(
            'Standard',
            $updated->service,
        );

        $this->assertSame(
            'MWT-10001',
            $updated->tracking_number,
        );

        $this->assertSame(
            'https://tracking.example.test/MWT-10001',
            $updated->tracking_url,
        );

        $this->assertSame(
            ShipmentStatus::Pending,
            $updated->status,
        );

        $audit =
            $updated->metadata[
                'tracking_last_updated'
            ] ?? null;

        $this->assertIsArray(
            $audit,
        );

        $this->assertSame(
            $admin->getMorphClass(),
            $audit['actor_type'] ?? null,
        );

        $this->assertSame(
            $admin->getKey(),
            $audit['actor_id'] ?? null,
        );
    }

    public function test_processing_shipment_tracking_can_be_updated(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            ShipmentStatus::Processing,
        );

        $updated = app(
            UpdateShipmentTrackingAction::class,
        )->execute(
            shipment: $shipment,

            carrier: 'Courier',

            service: null,

            trackingNumber: 'ABC-1',

            trackingUrl: null,
        );

        $this->assertSame(
            'Courier',
            $updated->carrier,
        );

        $this->assertSame(
            'ABC-1',
            $updated->tracking_number,
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $updated->status,
        );
    }

    public function test_shipped_shipment_tracking_can_be_corrected(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            ShipmentStatus::Shipped,
        );

        $updated = app(
            UpdateShipmentTrackingAction::class,
        )->execute(
            shipment: $shipment,

            carrier: 'Updated Courier',

            service: 'Express',

            trackingNumber: 'NEW-TRACKING',

            trackingUrl: 'https://tracking.example.test/NEW-TRACKING',
        );

        $this->assertSame(
            'NEW-TRACKING',
            $updated->tracking_number,
        );

        $this->assertSame(
            ShipmentStatus::Shipped,
            $updated->status,
        );
    }

    public function test_blank_values_are_normalized_to_null(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            ShipmentStatus::Pending,
        );

        $shipment->update([
            'carrier' => 'Old carrier',
            'service' => 'Old service',
            'tracking_number' => 'OLD',
            'tracking_url' => 'https://tracking.example.test/OLD',
        ]);

        $updated = app(
            UpdateShipmentTrackingAction::class,
        )->execute(
            shipment: $shipment,

            carrier: '   ',

            service: '',

            trackingNumber: '  ',

            trackingUrl: null,
        );

        $this->assertNull(
            $updated->carrier,
        );

        $this->assertNull(
            $updated->service,
        );

        $this->assertNull(
            $updated->tracking_number,
        );

        $this->assertNull(
            $updated->tracking_url,
        );
    }

    public function test_service_or_tracking_details_require_a_carrier(): void
    {
        ['shipment' => $shipment] = $this->shipmentFixture(
            ShipmentStatus::Processing,
        );

        try {
            app(UpdateShipmentTrackingAction::class)->execute(
                shipment: $shipment,
                carrier: null,
                service: 'Express',
                trackingNumber: 'TRACK-100',
                trackingUrl: 'https://tracking.example.test/TRACK-100',
            );

            $this->fail('Tracking details without a carrier should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('carrier', $exception->errors());
        }

        $shipment->refresh();
        $this->assertNull($shipment->carrier);
        $this->assertNull($shipment->service);
        $this->assertNull($shipment->tracking_number);
        $this->assertNull($shipment->tracking_url);
    }

    public function test_carrier_only_is_valid_for_a_manual_courier_without_online_tracking(): void
    {
        ['shipment' => $shipment] = $this->shipmentFixture(
            ShipmentStatus::Processing,
        );

        $updated = app(UpdateShipmentTrackingAction::class)->execute(
            shipment: $shipment,
            carrier: 'Own Delivery Team',
            service: null,
            trackingNumber: null,
            trackingUrl: null,
        );

        $this->assertSame('Own Delivery Team', $updated->carrier);
        $this->assertNull($updated->tracking_number);
        $this->assertNull($updated->tracking_url);
    }

    public function test_shipped_manual_shipment_cannot_clear_its_carrier(): void
    {
        ['shipment' => $shipment] = $this->shipmentFixture(
            ShipmentStatus::Shipped,
        );

        $shipment->update([
            'carrier' => 'Local Courier',
            'tracking_number' => 'LOCAL-100',
        ]);

        try {
            app(UpdateShipmentTrackingAction::class)->execute(
                shipment: $shipment->refresh(),
                carrier: null,
                service: null,
                trackingNumber: null,
                trackingUrl: null,
            );

            $this->fail('A shipped manual parcel must retain a carrier.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('carrier', $exception->errors());
        }

        $shipment->refresh();
        $this->assertSame('Local Courier', $shipment->carrier);
        $this->assertSame('LOCAL-100', $shipment->tracking_number);
    }

    public function test_external_provider_tracking_cannot_be_manually_overwritten(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            ShipmentStatus::Processing,
        );

        $shipment->update([
            'provider_code' => ShippingProviderCode::Pathao
                ->value,

            'carrier' => 'Pathao',

            'service' => 'Pathao Courier',

            'tracking_number' => 'PATHAO-ORIGINAL',

            'tracking_url' => 'https://tracking.example.test/PATHAO-ORIGINAL',
        ]);

        try {
            app(
                UpdateShipmentTrackingAction::class,
            )->execute(
                shipment: $shipment->refresh(),

                carrier: 'Manual Override',

                service: 'Wrong Service',

                trackingNumber: 'OVERRIDDEN',

                trackingUrl: 'https://example.test/OVERRIDDEN',
            );

            $this->fail(
                'External provider tracking must not be manually editable.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'tracking',
                $exception->errors(),
            );
        }

        $shipment->refresh();

        $this->assertSame(
            'Pathao',
            $shipment->carrier,
        );

        $this->assertSame(
            'Pathao Courier',
            $shipment->service,
        );

        $this->assertSame(
            'PATHAO-ORIGINAL',
            $shipment->tracking_number,
        );

        $this->assertSame(
            'https://tracking.example.test/PATHAO-ORIGINAL',
            $shipment->tracking_url,
        );
    }

    public function test_delivered_shipment_tracking_is_immutable(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            ShipmentStatus::Delivered,
        );

        $this->expectException(
            ValidationException::class,
        );

        app(
            UpdateShipmentTrackingAction::class,
        )->execute(
            shipment: $shipment,

            carrier: 'Courier',

            service: null,

            trackingNumber: 'TRACK',

            trackingUrl: null,
        );
    }

    public function test_returned_shipment_tracking_is_immutable(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            ShipmentStatus::Returned,
        );

        $this->expectException(
            ValidationException::class,
        );

        app(
            UpdateShipmentTrackingAction::class,
        )->execute(
            shipment: $shipment,

            carrier: 'Courier',

            service: null,

            trackingNumber: 'TRACK',

            trackingUrl: null,
        );
    }

    public function test_cancelled_shipment_tracking_is_immutable(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            ShipmentStatus::Cancelled,
        );

        $this->expectException(
            ValidationException::class,
        );

        app(
            UpdateShipmentTrackingAction::class,
        )->execute(
            shipment: $shipment,

            carrier: 'Courier',

            service: null,

            trackingNumber: 'TRACK',

            trackingUrl: null,
        );
    }

    /**
     * @return array{
     *     shipment: Shipment,
     *     admin: Admin
     * }
     */
    private function shipmentFixture(
        ShipmentStatus $status,
    ): array {
        $admin = Admin::factory()
            ->create();

        $user = User::factory()
            ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
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
                'status' => $status,
            ]);

        return [
            'shipment' => $shipment,

            'admin' => $admin,
        ];
    }
}
