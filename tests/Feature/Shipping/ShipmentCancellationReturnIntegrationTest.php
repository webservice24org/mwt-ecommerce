<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\CancelShipmentAction;
use App\Domain\Shipping\Actions\CreateShipmentAction;
use App\Domain\Shipping\Actions\ReturnShipmentAction;
use App\Domain\Shipping\Data\CreateShipmentItemData;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ShipmentCancellationReturnIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_shipment_can_be_cancelled(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment = Shipment::factory()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'status' => ShipmentStatus::Pending,
            ]);

        $cancelled = app(
            CancelShipmentAction::class,
        )->execute(
            shipment: $shipment,
            reason: 'Warehouse allocation replaced.',
        );

        $this->assertSame(
            ShipmentStatus::Cancelled,
            $cancelled->status,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );
    }

    public function test_processing_shipment_can_be_cancelled(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment = Shipment::factory()
            ->processing()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        $cancelled = app(
            CancelShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );

        $this->assertSame(
            ShipmentStatus::Cancelled,
            $cancelled->status,
        );
    }

    public function test_cancelled_shipment_releases_allocation_for_replacement(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 5,
            ]);

        $createShipment = app(
            CreateShipmentAction::class,
        );

        $firstShipment =
            $createShipment->execute(
                order: $order,

                shippingMethod: $shippingMethod,

                items: [
                    new CreateShipmentItemData(
                        orderItemId: $orderItem->id,

                        quantity: 5,
                    ),
                ],
            );

        app(
            CancelShipmentAction::class,
        )->execute(
            shipment: $firstShipment,
        );

        $replacement =
            $createShipment->execute(
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
            $replacement->status,
        );

        $this->assertSame(
            5,
            $replacement
                ->items
                ->firstOrFail()
                ->quantity,
        );
    }

    public function test_shipped_shipment_cannot_be_cancelled(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $shipment = Shipment::factory()
            ->shipped()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            CancelShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );
    }

    public function test_delivered_shipment_can_be_returned(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->deliveredOrder();

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 2,
            ]);

        $shipment = Shipment::factory()
            ->delivered()
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
                'quantity' => 2,
            ]);

        $returned = app(
            ReturnShipmentAction::class,
        )->execute(
            shipment: $shipment,
            note: 'Customer return received.',
        );

        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Returned,
            $returned->status,
        );

        $this->assertNotNull(
            $returned->returned_at,
        );

        $this->assertSame(
            FulfilmentStatus::Returned,
            $order->fulfilment_status,
        );
    }

    public function test_partial_return_does_not_mark_order_returned(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->deliveredOrder();

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 5,
            ]);

        $firstShipment = Shipment::factory()
            ->delivered()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        $secondShipment = Shipment::factory()
            ->delivered()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        ShipmentItem::factory()
            ->for($firstShipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        ShipmentItem::factory()
            ->for($secondShipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        app(
            ReturnShipmentAction::class,
        )->execute(
            shipment: $firstShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order->fulfilment_status,
        );
    }

    public function test_final_return_marks_order_returned_once(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->deliveredOrder();

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 5,
            ]);

        $firstShipment = Shipment::factory()
            ->delivered()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        $secondShipment = Shipment::factory()
            ->delivered()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        ShipmentItem::factory()
            ->for($firstShipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        ShipmentItem::factory()
            ->for($secondShipment)
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        $action = app(
            ReturnShipmentAction::class,
        );

        $action->execute(
            shipment: $firstShipment,
        );

        $action->execute(
            shipment: $secondShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Returned,
            $order->fulfilment_status,
        );

        $this->assertSame(
            1,
            $order
                ->statusHistories()
                ->where(
                    'type',
                    OrderStatusHistory::TYPE_FULFILMENT,
                )
                ->where(
                    'to_status',
                    FulfilmentStatus::Returned
                        ->value,
                )
                ->count(),
        );
    }

    public function test_shipped_shipment_can_return_before_delivery(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $orderItem = OrderItem::factory()
            ->for($order)
            ->create([
                'quantity' => 1,
            ]);

        $shipment = Shipment::factory()
            ->shipped()
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
                'quantity' => 1,
            ]);

        app(
            ReturnShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );

        $shipment->refresh();
        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Returned,
            $shipment->status,
        );

        $this->assertSame(
            FulfilmentStatus::Returned,
            $order->fulfilment_status,
        );
    }

    public function test_return_does_not_restore_product_stock(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->deliveredOrder();

        $product = Product::factory()
            ->published()
            ->withStock(7)
            ->create();

        $orderItem = OrderItem::factory()
            ->for($order)
            ->for($product)
            ->create([
                'quantity' => 2,
            ]);

        $shipment = Shipment::factory()
            ->delivered()
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
                'quantity' => 2,
            ]);

        app(
            ReturnShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );

        $product->refresh();

        $this->assertSame(
            7,
            $product->stock_quantity,
        );
    }

    public function test_pending_shipment_cannot_be_returned(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment = Shipment::factory()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            ReturnShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );
    }

    /**
     * @return array{
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function processingOrder(): array
    {
        return $this->orderWithFulfilment(
            FulfilmentStatus::Processing,
            OrderStatus::Processing,
        );
    }

    /**
     * @return array{
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function shippedOrder(): array
    {
        return $this->orderWithFulfilment(
            FulfilmentStatus::Shipped,
            OrderStatus::Processing,
        );
    }

    /**
     * @return array{
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function deliveredOrder(): array
    {
        return $this->orderWithFulfilment(
            FulfilmentStatus::Delivered,
            OrderStatus::Processing,
        );
    }

    /**
     * @return array{
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function orderWithFulfilment(
        FulfilmentStatus $fulfilmentStatus,
        OrderStatus $orderStatus,
    ): array {
        $user = User::factory()
            ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'is_active' => true,
                ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => $orderStatus,

                'fulfilment_status' => $fulfilmentStatus,

                'shipping_method_id' => $shippingMethod->id,
            ]);

        return [
            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ];
    }
}
