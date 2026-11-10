<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\DeliverShipmentAction;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class DeliverShipmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_single_fully_delivered_shipment_marks_order_delivered(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 2,
            ]);

        $shipment = Shipment::factory()
            ->shipped()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        ShipmentItem::factory()
            ->for(
                $shipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        $deliveredShipment = app(
            DeliverShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );

        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Delivered,
            $deliveredShipment->status,
        );

        $this->assertNotNull(
            $deliveredShipment
                ->delivered_at,
        );

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order
                ->fulfilment_status,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_FULFILMENT,

                'from_status' => FulfilmentStatus::Shipped
                    ->value,

                'to_status' => FulfilmentStatus::Delivered
                    ->value,
            ],
        );
    }

    public function test_partial_delivery_keeps_order_shipped(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 5,
            ]);

        $firstShipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        $secondShipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        ShipmentItem::factory()
            ->for(
                $firstShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        ShipmentItem::factory()
            ->for(
                $secondShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        app(
            DeliverShipmentAction::class,
        )->execute(
            shipment: $firstShipment,
        );

        $order->refresh();
        $firstShipment->refresh();

        $this->assertSame(
            ShipmentStatus::Delivered,
            $firstShipment->status,
        );

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order
                ->fulfilment_status,
        );

        $this->assertDatabaseMissing(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_FULFILMENT,

                'to_status' => FulfilmentStatus::Delivered
                    ->value,
            ],
        );
    }

    public function test_final_partial_delivery_moves_order_to_delivered(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 5,
            ]);

        $firstShipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        $secondShipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        ShipmentItem::factory()
            ->for(
                $firstShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        ShipmentItem::factory()
            ->for(
                $secondShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        $action = app(
            DeliverShipmentAction::class,
        );

        $action->execute(
            shipment: $firstShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order
                ->fulfilment_status,
        );

        $action->execute(
            shipment: $secondShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order
                ->fulfilment_status,
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
                    FulfilmentStatus::Delivered
                        ->value,
                )
                ->count(),
        );
    }

    public function test_every_order_line_must_be_fully_delivered(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $firstOrderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 2,
                ]);

        $secondOrderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 3,
                ]);

        $firstShipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        $secondShipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        ShipmentItem::factory()
            ->for(
                $firstShipment,
            )
            ->for(
                $firstOrderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        ShipmentItem::factory()
            ->for(
                $secondShipment,
            )
            ->for(
                $secondOrderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        $action = app(
            DeliverShipmentAction::class,
        );

        $action->execute(
            shipment: $firstShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order
                ->fulfilment_status,
        );

        $action->execute(
            shipment: $secondShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order
                ->fulfilment_status,
        );
    }

    public function test_physical_delivery_can_happen_while_order_aggregate_is_processing(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 5,
            ]);

        /*
         * First parcel has already shipped.
         */
        $firstShipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        ShipmentItem::factory()
            ->for(
                $firstShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        /*
         * Remaining parcel is not shipped yet, therefore
         * the aggregate order remains Processing.
         */
        $secondShipment =
            Shipment::factory()
                ->processing()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        ShipmentItem::factory()
            ->for(
                $secondShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        $deliveredShipment = app(
            DeliverShipmentAction::class,
        )->execute(
            shipment: $firstShipment,
        );

        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Delivered,
            $deliveredShipment->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order
                ->fulfilment_status,
        );
    }

    public function test_returned_quantity_does_not_count_as_currently_delivered(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 5,
            ]);

        $returnedShipment =
            Shipment::factory()
                ->returned()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        ShipmentItem::factory()
            ->for(
                $returnedShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        $shipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        ShipmentItem::factory()
            ->for(
                $shipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        app(
            DeliverShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order
                ->fulfilment_status,
        );
    }

    public function test_processing_shipment_cannot_skip_shipped_state(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment =
            Shipment::factory()
                ->processing()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        try {
            app(
                DeliverShipmentAction::class,
            )->execute(
                shipment: $shipment,
            );

            $this->fail(
                'Processing shipment must not skip the shipped state.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'shipment_status',
                $exception->errors(),
            );
        }

        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment->status,
        );
    }

    public function test_delivered_shipment_cannot_be_delivered_twice(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->shippedOrder();

        $shipment =
            Shipment::factory()
                ->delivered()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            DeliverShipmentAction::class,
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
    private function shippedOrder(): array
    {
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

                'fulfilment_status' => FulfilmentStatus::Shipped,

                'shipping_method_id' => $shippingMethod->id,
            ]);

        return [
            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ];
    }

    /**
     * @return array{
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function processingOrder(): array
    {
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

        return [
            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ];
    }
}
