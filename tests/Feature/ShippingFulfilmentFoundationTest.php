<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShippingFulfilmentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_defaults_to_unfulfilled(): void
    {
        $order = Order::factory()->create();

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );
    }

    public function test_shipping_method_can_be_created(): void
    {
        $method = ShippingMethod::query()->create([
            'name' => 'Standard Delivery',
            'code' => 'standard',
            'driver' => 'manual',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->assertDatabaseHas(
            'shipping_methods',
            [
                'id' => $method->id,
                'code' => 'standard',
                'is_active' => true,
            ],
        );
    }

    public function test_order_can_have_multiple_shipments(): void
    {
        $order = Order::factory()->create();

        $first = Shipment::query()->create([
            'order_id' => $order->id,
            'status' => ShipmentStatus::Pending,
        ]);

        $second = Shipment::query()->create([
            'order_id' => $order->id,
            'status' => ShipmentStatus::Pending,
        ]);

        $order->refresh();

        $this->assertCount(
            2,
            $order->shipments,
        );

        $this->assertTrue(
            $order->shipments->contains(
                $first,
            ),
        );

        $this->assertTrue(
            $order->shipments->contains(
                $second,
            ),
        );
    }

    public function test_shipment_status_is_cast_to_enum(): void
    {
        $order = Order::factory()->create();

        $shipment = Shipment::query()->create([
            'order_id' => $order->id,
            'status' => ShipmentStatus::Shipped,
        ]);

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shipment->status,
        );
    }
}
