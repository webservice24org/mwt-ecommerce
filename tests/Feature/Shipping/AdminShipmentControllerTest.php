<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminShipmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_shipment_for_order(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 3,
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.store',
                    $order,
                ),
                [
                    'shipping_method_id' => $shippingMethod->id,

                    'carrier' => 'Pathao',

                    'service' => 'Standard',

                    'items' => [
                        [
                            'order_item_id' => $orderItem->id,

                            'quantity' => 2,
                        ],
                    ],
                ],
            );

        $response->assertRedirect(
            route(
                'admin.orders.show',
                $order,
            ),
        );

        $shipment = Shipment::query()
            ->where(
                'order_id',
                $order->id,
            )
            ->firstOrFail();

        $this->assertSame(
            ShipmentStatus::Pending,
            $shipment->status,
        );

        $this->assertSame(
            'Pathao',
            $shipment->carrier,
        );

        $this->assertDatabaseHas(
            'shipment_items',
            [
                'shipment_id' => $shipment->id,

                'order_item_id' => $orderItem->id,

                'quantity' => 2,
            ],
        );
    }

    public function test_admin_cannot_allocate_item_from_another_order(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        [
            'order' => $otherOrder,
        ] = $this->processingOrder();

        $otherItem = OrderItem::factory()
            ->for(
                $otherOrder,
            )
            ->create([
                'quantity' => 1,
            ]);

        $response = $this
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
                    'admin.orders.shipments.store',
                    $order,
                ),
                [
                    'shipping_method_id' => $shippingMethod->id,

                    'items' => [
                        [
                            'order_item_id' => $otherItem->id,

                            'quantity' => 1,
                        ],
                    ],
                ],
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseMissing(
            'shipments',
            [
                'order_id' => $order->id,
            ],
        );
    }

    public function test_nested_shipment_route_rejects_shipment_from_another_order(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
        ] = $this->processingOrder();

        [
            'order' => $otherOrder,
            'shippingMethod' => $otherMethod,
        ] = $this->processingOrder();

        $shipment = Shipment::factory()
            ->processing()
            ->for(
                $otherOrder,
            )
            ->for(
                $otherMethod,
                'shippingMethod',
            )
            ->create();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.ship',
                    [
                        $order,
                        $shipment,
                    ],
                ),
                [
                    'note' => 'Should not be accepted.',
                ],
            );

        $response->assertNotFound();

        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment->status,
        );
    }

    public function test_admin_can_start_shipment_processing(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment = Shipment::factory()
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

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.start-processing',
                    [
                        $order,
                        $shipment,
                    ],
                ),
                [
                    'note' => 'Picking started.',
                ],
            );

        $response->assertRedirect(
            route(
                'admin.orders.show',
                $order,
            ),
        );

        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment->status,
        );
    }

    public function test_admin_can_update_tracking_details(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment = Shipment::factory()
            ->processing()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->patch(
                route(
                    'admin.orders.shipments.tracking.update',
                    [
                        $order,
                        $shipment,
                    ],
                ),
                [
                    'carrier' => 'Pathao',

                    'service' => 'Express',

                    'tracking_number' => 'TRK-100',

                    'tracking_url' => 'https://tracking.example.test/TRK-100',
                ],
            );

        $response->assertRedirect(
            route(
                'admin.orders.show',
                $order,
            ),
        );

        $shipment->refresh();

        $this->assertSame(
            'TRK-100',
            $shipment->tracking_number,
        );
    }

    public function test_shipping_final_shipment_reconciles_order_to_shipped(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 1,
            ]);

        $shipment = Shipment::factory()
            ->processing()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'carrier' => 'Own Delivery Team',
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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.ship',
                    [
                        $order,
                        $shipment,
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $shipment->refresh();
        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Shipped,
            $shipment->status,
        );

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order->fulfilment_status,
        );
    }

    public function test_delivery_final_shipment_reconciles_order_to_delivered(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->orderWithFulfilment(
            FulfilmentStatus::Shipped,
        );

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 1,
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
                'quantity' => 1,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.deliver',
                    [
                        $order,
                        $shipment,
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $shipment->refresh();
        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Delivered,
            $shipment->status,
        );

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order->fulfilment_status,
        );
    }

    public function test_admin_can_cancel_pending_shipment(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $shipment = Shipment::factory()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.cancel',
                    [
                        $order,
                        $shipment,
                    ],
                ),
                [
                    'reason' => 'Reallocating parcel.',
                ],
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Cancelled,
            $shipment->status,
        );
    }

    public function test_return_final_shipment_reconciles_order_to_returned(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->orderWithFulfilment(
            FulfilmentStatus::Delivered,
        );

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 1,
            ]);

        $shipment = Shipment::factory()
            ->delivered()
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
                'quantity' => 1,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.shipments.return',
                    [
                        $order,
                        $shipment,
                    ],
                ),
                [
                    'note' => 'Return received.',
                ],
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
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

    /**
     * @return array{
     *     admin: Admin,
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function processingOrder(): array
    {
        return $this->orderWithFulfilment(
            FulfilmentStatus::Processing,
        );
    }

    /**
     * @return array{
     *     admin: Admin,
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function orderWithFulfilment(
        FulfilmentStatus $fulfilmentStatus,
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

                'fulfilment_status' => $fulfilmentStatus,

                'shipping_method_id' => $shippingMethod->id,
            ]);

        return [
            'admin' => $admin,

            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ];
    }
}
