<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\ShipShipmentAction;
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

final class ShipShipmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_fully_allocated_single_shipment_marks_order_shipped(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 2,
                ]);

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

        $shippedShipment = app(
            ShipShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );

        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shippedShipment->status,
        );

        $this->assertNotNull(
            $shippedShipment->shipped_at,
        );

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order->fulfilment_status,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_FULFILMENT,

                'from_status' => FulfilmentStatus::Processing
                    ->value,

                'to_status' => FulfilmentStatus::Shipped
                    ->value,
            ],
        );
    }

    public function test_partial_shipment_keeps_order_processing(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 5,
                ]);

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

        app(
            ShipShipmentAction::class,
        )->execute(
            shipment: $shipment,
        );

        $order->refresh();
        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shipment->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

        $this->assertDatabaseMissing(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_FULFILMENT,

                'to_status' => FulfilmentStatus::Shipped
                    ->value,
            ],
        );
    }

    public function test_final_partial_shipment_moves_order_to_shipped(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 5,
                ]);

        $firstShipment =
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
            ShipShipmentAction::class,
        );

        $action->execute(
            shipment: $firstShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

        $action->execute(
            shipment: $secondShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Shipped,
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
                    FulfilmentStatus::Shipped
                        ->value,
                )
                ->count(),
        );
    }

    public function test_every_order_line_must_be_fully_shipped(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

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
                $firstShipment,
            )
            ->for(
                $firstOrderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

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
                $secondOrderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        $action = app(
            ShipShipmentAction::class,
        );

        $action->execute(
            shipment: $firstShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

        $action->execute(
            shipment: $secondShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order->fulfilment_status,
        );
    }

    public function test_cancelled_allocation_does_not_count_as_shipped_quantity(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 5,
                ]);

        $cancelledShipment =
            Shipment::factory()
                ->cancelled()
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
                $cancelledShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 3,
            ]);

        $activeShipment =
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
                $activeShipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        app(
            ShipShipmentAction::class,
        )->execute(
            shipment: $activeShipment,
        );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );
    }

    public function test_pending_shipment_cannot_skip_processing_and_ship_directly(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 1,
                ]);

        $shipment =
            Shipment::factory()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create([
                    'status' => ShipmentStatus::Pending,
                ]);

        ShipmentItem::factory()
            ->for(
                $shipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 1,
            ]);

        try {
            app(
                ShipShipmentAction::class,
            )->execute(
                shipment: $shipment,
            );

            $this->fail(
                'Pending shipment must not skip processing.',
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
        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Pending,
            $shipment->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );
    }

    public function test_cancelled_shipment_cannot_be_shipped(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment =
            Shipment::factory()
                ->cancelled()
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
            ShipShipmentAction::class,
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
