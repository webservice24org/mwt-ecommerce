<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Queries\GetAdminOrderShippingReadModelQuery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminOrderShippingReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_allocation_progress_and_excludes_cancelled_allocations(): void
    {
        $user = User::factory()
            ->create();

        $shippingMethod = ShippingMethod::factory()
            ->create([
                'is_active' => true,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,

                'shipping_method_id' => $shippingMethod->id,

                'shipping_method_code' => $shippingMethod->code,

                'shipping_method_name' => $shippingMethod->name,
            ]);

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 5,
            ]);

        $activeShipment = Shipment::factory()
            ->processing()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'carrier' => 'Test Courier',
                'service' => 'Standard',
                'tracking_number' => 'MWT-10001',
                'tracking_url' => 'https://tracking.example.test/MWT-10001',
            ]);

        ShipmentItem::factory()
            ->for($activeShipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        $cancelledShipment = Shipment::factory()
            ->cancelled()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        ShipmentItem::factory()
            ->for($cancelledShipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 1,
            ]);

        $shipping = app(
            GetAdminOrderShippingReadModelQuery::class,
        )->get(
            $order,
        );

        $this->assertTrue(
            $shipping['create_allowed'],
        );

        $this->assertSame(
            5,
            $shipping['summary']['total_ordered_quantity'],
        );

        $this->assertSame(
            2,
            $shipping['summary']['total_active_allocated_quantity'],
        );

        $this->assertSame(
            3,
            $shipping['summary']['total_remaining_quantity'],
        );

        $this->assertSame(
            2,
            $shipping['items'][0]['active_allocated_quantity'],
        );

        $this->assertSame(
            3,
            $shipping['items'][0]['remaining_quantity'],
        );

        $this->assertCount(
            2,
            $shipping['shipments'],
        );

        $this->assertSame(
            ShipmentStatus::Processing->value,
            $shipping['shipments'][0]['status']['value'],
        );

        $this->assertSame(
            'MWT-10001',
            $shipping['shipments'][0]['tracking_number'],
        );

        $this->assertTrue(
            $shipping['shipments'][0]['actions']['can_update_tracking'],
        );

        $this->assertTrue(
            $shipping['shipments'][0]['actions']['can_ship'],
        );

        $this->assertTrue(
            $shipping['shipments'][0]['actions']['can_cancel'],
        );

        $this->assertFalse(
            $shipping['shipments'][0]['actions']['can_deliver'],
        );

        $this->assertSame(
            ShipmentStatus::Cancelled->value,
            $shipping['shipments'][1]['status']['value'],
        );

        $this->assertFalse(
            $shipping['shipments'][1]['actions']['can_start_processing'],
        );

        $this->assertFalse(
            $shipping['shipments'][1]['actions']['can_update_tracking'],
        );

        $this->assertFalse(
            $shipping['shipments'][1]['actions']['can_ship'],
        );

        $this->assertFalse(
            $shipping['shipments'][1]['actions']['can_cancel'],
        );
    }

    public function test_fully_allocated_order_cannot_offer_another_shipment(): void
    {
        $user = User::factory()
            ->create();

        $shippingMethod = ShippingMethod::factory()
            ->create([
                'is_active' => true,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,

                'shipping_method_id' => $shippingMethod->id,

                'shipping_method_code' => $shippingMethod->code,

                'shipping_method_name' => $shippingMethod->name,
            ]);

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 3,
            ]);

        $shipment = Shipment::factory()
            ->processing()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        ShipmentItem::factory()
            ->for($shipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        $shipping = app(
            GetAdminOrderShippingReadModelQuery::class,
        )->get(
            $order,
        );

        $this->assertFalse(
            $shipping['create_allowed'],
        );

        $this->assertSame(
            0,
            $shipping['summary']['total_remaining_quantity'],
        );
    }
}
