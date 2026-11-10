<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\CreateShipmentAction;
use App\Domain\Shipping\Data\CreateShipmentItemData;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CreateShipmentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_shipment_with_allocated_items(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'is_active' => true,
                ]);

        $order = $this
            ->createOrder(
                $shippingMethod,
            );

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 5,
            ]);

        $shipment = app(
            CreateShipmentAction::class,
        )->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 2,
                ),
            ],

            carrier: 'Test Courier',

            service: 'Standard',
        );

        $this->assertSame(
            $order->id,
            $shipment->order_id,
        );

        $this->assertSame(
            $shippingMethod->id,
            $shipment
                ->shipping_method_id,
        );

        $this->assertSame(
            ShipmentStatus::Pending,
            $shipment->status,
        );

        $this->assertSame(
            'Test Courier',
            $shipment->carrier,
        );

        $this->assertSame(
            'Standard',
            $shipment->service,
        );

        $this->assertCount(
            1,
            $shipment->items,
        );

        $shipmentItem =
            $shipment->items
                ->first();

        $this->assertNotNull(
            $shipmentItem,
        );

        $this->assertSame(
            $orderItem->id,
            $shipmentItem
                ->order_item_id,
        );

        $this->assertSame(
            2,
            $shipmentItem
                ->quantity,
        );

        /*
         * Shipment allocation does not yet mutate the
         * aggregate order lifecycle.
         */
        $order->refresh();

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order
                ->fulfilment_status,
        );
    }

    public function test_it_rejects_order_item_from_another_order(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $order = $this
            ->createOrder(
                $shippingMethod,
            );

        $otherOrder = $this
            ->createOrder(
                $shippingMethod,
            );

        $otherOrderItem =
            OrderItem::factory()
                ->for($otherOrder)
                ->create([
                    'quantity' => 2,
                ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            CreateShipmentAction::class,
        )->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $otherOrderItem->id,

                    quantity: 1,
                ),
            ],
        );
    }

    public function test_it_rejects_over_allocation_across_shipments(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $order = $this
            ->createOrder(
                $shippingMethod,
            );

        $orderItem =
            OrderItem::factory()
                ->for($order)
                ->create([
                    'quantity' => 5,
                ]);

        $action = app(
            CreateShipmentAction::class,
        );

        $action->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 4,
                ),
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $action->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 2,
                ),
            ],
        );
    }

    public function test_cancelled_shipment_releases_its_allocation(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $order = $this
            ->createOrder(
                $shippingMethod,
            );

        $orderItem =
            OrderItem::factory()
                ->for($order)
                ->create([
                    'quantity' => 5,
                ]);

        $action = app(
            CreateShipmentAction::class,
        );

        $firstShipment =
            $action->execute(
                order: $order,

                shippingMethod: $shippingMethod,

                items: [
                    new CreateShipmentItemData(
                        orderItemId: $orderItem->id,

                        quantity: 4,
                    ),
                ],
            );

        $firstShipment->update([
            'status' => ShipmentStatus::Cancelled,
        ]);

        $secondShipment =
            $action->execute(
                order: $order,

                shippingMethod: $shippingMethod,

                items: [
                    new CreateShipmentItemData(
                        orderItemId: $orderItem->id,

                        quantity: 5,
                    ),
                ],
            );

        $this->assertSame(
            ShipmentStatus::Pending,
            $secondShipment->status,
        );

        $this->assertSame(
            5,
            $secondShipment
                ->items
                ->firstOrFail()
                ->quantity,
        );
    }

    public function test_duplicate_order_item_in_same_request_is_rejected(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $order = $this
            ->createOrder(
                $shippingMethod,
            );

        $orderItem =
            OrderItem::factory()
                ->for($order)
                ->create([
                    'quantity' => 5,
                ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            CreateShipmentAction::class,
        )->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 1,
                ),

                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 1,
                ),
            ],
        );
    }

    public function test_zero_quantity_is_rejected(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $order = $this
            ->createOrder(
                $shippingMethod,
            );

        $orderItem =
            OrderItem::factory()
                ->for($order)
                ->create([
                    'quantity' => 5,
                ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            CreateShipmentAction::class,
        )->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 0,
                ),
            ],
        );
    }

    public function test_inactive_shipping_method_is_rejected(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'is_active' => false,
                ]);

        $order = $this
            ->createOrder(
                $shippingMethod,
            );

        $orderItem =
            OrderItem::factory()
                ->for($order)
                ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            CreateShipmentAction::class,
        )->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 1,
                ),
            ],
        );
    }

    public function test_shipping_method_must_match_order_selection(): void
    {
        $orderShippingMethod =
            ShippingMethod::factory()
                ->create();

        $otherShippingMethod =
            ShippingMethod::factory()
                ->create();

        $order = $this
            ->createOrder(
                $orderShippingMethod,
            );

        $orderItem =
            OrderItem::factory()
                ->for($order)
                ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            CreateShipmentAction::class,
        )->execute(
            order: $order,

            shippingMethod: $otherShippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 1,
                ),
            ],
        );
    }

    public function test_shipped_order_cannot_receive_new_shipment(): void
    {
        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $order = $this
            ->createOrder(
                $shippingMethod,
                fulfilmentStatus: FulfilmentStatus::Shipped,
            );

        $orderItem =
            OrderItem::factory()
                ->for($order)
                ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            CreateShipmentAction::class,
        )->execute(
            order: $order,

            shippingMethod: $shippingMethod,

            items: [
                new CreateShipmentItemData(
                    orderItemId: $orderItem->id,

                    quantity: 1,
                ),
            ],
        );
    }

    private function createOrder(
        ShippingMethod $shippingMethod,
        FulfilmentStatus $fulfilmentStatus =
        FulfilmentStatus::Processing,
    ): Order {
        $user = User::factory()
            ->create();

        return Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => $fulfilmentStatus,

                'shipping_method_id' => $shippingMethod->id,
            ]);
    }
}
