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
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminFulfilmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_guest_cannot_create_admin_shipment(): void
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
                'quantity' => 1,
            ]);

        $response = $this->post(
            route(
                'admin.orders.shipments.store',
                $order,
            ),
            [
                'shipping_method_id' => $shippingMethod->id,

                'items' => [
                    [
                        'order_item_id' => $orderItem->id,

                        'quantity' => 1,
                    ],
                ],
            ],
        );

        $response->assertRedirect(
            route(
                'admin.login',
            ),
        );

        $this->assertDatabaseMissing(
            'shipments',
            [
                'order_id' => $order->id,
            ],
        );
    }

    public function test_customer_session_cannot_use_admin_shipment_endpoint(): void
    {
        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $customer = User::factory()
            ->create();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 1,
            ]);

        $response = $this
            ->actingAs(
                $customer,
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
                            'order_item_id' => $orderItem->id,

                            'quantity' => 1,
                        ],
                    ],
                ],
            );

        $response->assertRedirect(
            route(
                'admin.login',
            ),
        );

        $this->assertDatabaseMissing(
            'shipments',
            [
                'order_id' => $order->id,
            ],
        );
    }

    public function test_http_creation_cannot_overallocate_order_item(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 2,
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
                            'order_item_id' => $orderItem->id,

                            'quantity' => 3,
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

    public function test_duplicate_order_item_rows_are_rejected_before_domain_action(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 2,
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
                            'order_item_id' => $orderItem->id,

                            'quantity' => 1,
                        ],
                        [
                            'order_item_id' => $orderItem->id,

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

    public function test_create_endpoint_ignores_protected_shipment_fields(): void
    {
        $admin = Admin::factory()
            ->create();

        [
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

        $this
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

                    'carrier' => 'Safe Carrier',

                    'items' => [
                        [
                            'order_item_id' => $orderItem->id,

                            'quantity' => 1,
                        ],
                    ],

                    /*
                     * These fields are deliberately not
                     * part of the validated/controller
                     * contract and must never control state.
                     */
                    'status' => ShipmentStatus::Delivered->value,

                    'tracking_number' => 'INJECTED',

                    'shipped_at' => now()->toISOString(),

                    'delivered_at' => now()->toISOString(),

                    'returned_at' => now()->toISOString(),

                    'metadata' => [
                        'privileged' => true,
                    ],
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
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

        $this->assertNull(
            $shipment->tracking_number,
        );

        $this->assertNull(
            $shipment->shipped_at,
        );

        $this->assertNull(
            $shipment->delivered_at,
        );

        $this->assertNull(
            $shipment->returned_at,
        );

        $this->assertSame(
            'Safe Carrier',
            $shipment->carrier,
        );
    }

    public function test_tracking_request_rejects_non_http_url(): void
    {
        $admin = Admin::factory()
            ->create();

        [
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
            ->create([
                'tracking_url' => null,
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
            ->patch(
                route(
                    'admin.orders.shipments.tracking.update',
                    [
                        $order,
                        $shipment,
                    ],
                ),
                [
                    'tracking_url' => 'javascript:alert(1)',
                ],
            );

        $response->assertSessionHasErrors([
            'tracking_url',
        ]);

        $this->assertNull(
            $shipment
                ->refresh()
                ->tracking_url,
        );
    }

    public function test_delivered_shipment_tracking_cannot_be_changed_through_http(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->orderWithFulfilment(
            FulfilmentStatus::Delivered,
        );

        $shipment = Shipment::factory()
            ->delivered()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'tracking_number' => 'ORIGINAL',
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
            ->patch(
                route(
                    'admin.orders.shipments.tracking.update',
                    [
                        $order,
                        $shipment,
                    ],
                ),
                [
                    'tracking_number' => 'CHANGED',
                ],
            );

        $response->assertSessionHasErrors([
            'tracking',
        ]);

        $this->assertSame(
            'ORIGINAL',
            $shipment
                ->refresh()
                ->tracking_number,
        );
    }

    public function test_cross_order_tracking_route_returns_not_found(): void
    {
        $admin = Admin::factory()
            ->create();

        [
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
            ->create([
                'tracking_number' => 'ORIGINAL',
            ]);

        $this
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
                    'tracking_number' => 'ATTACK',
                ],
            )
            ->assertNotFound();

        $this->assertSame(
            'ORIGINAL',
            $shipment
                ->refresh()
                ->tracking_number,
        );
    }

    public function test_order_cancellation_is_blocked_while_active_physical_shipment_exists(): void
    {
        $admin = Admin::factory()
            ->create();

        [
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
            ->from(
                route(
                    'admin.orders.show',
                    $order,
                ),
            )
            ->post(
                route(
                    'admin.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Attempt cancellation with active shipment.',
                ],
            );

        $response->assertSessionHasErrors([
            'cancellation',
        ]);

        $this->assertSame(
            OrderStatus::Processing,
            $order
                ->refresh()
                ->status,
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment
                ->refresh()
                ->status,
        );
    }

    public function test_order_page_hides_cancellation_while_active_shipment_exists(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'order.cancellation.allowed',
                        false,
                    ),
            );
    }

    public function test_cancelled_shipment_does_not_permanently_lock_order_cancellation_ui(): void
    {
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
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,

                'shipping_method_id' => $shippingMethod->id,

                'shipping_method_code' => $shippingMethod->code,

                'shipping_method_name' => $shippingMethod->name,
            ]);

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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'order.cancellation.allowed',
                        true,
                    ),
            );
    }

    public function test_legacy_aggregate_ship_endpoint_is_blocked_once_physical_shipment_exists(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

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
                    'admin.orders.ship',
                    $order,
                ),
                [
                    'note' => 'Attempt aggregate bypass.',
                ],
            );

        $response->assertSessionHasErrors([
            'fulfilment_status',
        ]);

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order
                ->refresh()
                ->fulfilment_status,
        );
    }

    public function test_legacy_aggregate_deliver_endpoint_is_blocked_once_physical_shipment_exists(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->orderWithFulfilment(
            FulfilmentStatus::Shipped,
        );

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
                    'admin.orders.deliver',
                    $order,
                ),
                [
                    'note' => 'Attempt aggregate bypass.',
                ],
            );

        $response->assertSessionHasErrors([
            'fulfilment_status',
        ]);

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order
                ->refresh()
                ->fulfilment_status,
        );
    }

    public function test_legacy_aggregate_ship_remains_available_for_order_without_shipments(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
        ] = $this->processingOrder();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.ship',
                    $order,
                ),
                [
                    'note' => 'Legacy fulfilment path.',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order
                ->refresh()
                ->fulfilment_status,
        );
    }

    public function test_admin_order_page_does_not_advertise_legacy_ship_action_when_shipment_exists(): void
    {
        $admin = Admin::factory()
            ->create();

        [
            'order' => $order,
            'shippingMethod' => $shippingMethod,
        ] = $this->processingOrder();

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

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Orders/Show',
                    )
                    ->has(
                        'order.available_actions',
                        0,
                    ),
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
    ): array {
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

                'shipping_method_code' => $shippingMethod->code,

                'shipping_method_name' => $shippingMethod->name,
            ]);

        return [
            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ];
    }
}
