<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class AdminOrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_cannot_confirm_order(): void
    {
        $order = $this->pendingOrder();

        $this
            ->post(
                route(
                    'admin.orders.confirm',
                    $order,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );
    }

    public function test_admin_can_move_order_through_full_processing_lifecycle(): void
    {
        $admin = $this->createAdmin();

        $order = $this->pendingOrder();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.confirm',
                    $order,
                ),
                [
                    'note' => 'Confirmed by admin.',
                ],
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $order->refresh();

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->status,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.start-processing',
                    $order,
                ),
                [
                    'note' => 'Warehouse started processing.',
                ],
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $order->refresh();

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

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
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order->fulfilment_status,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.deliver',
                    $order,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order->fulfilment_status,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.complete',
                    $order,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $order->refresh();

        $this->assertSame(
            OrderStatus::Completed,
            $order->status,
        );

        /*
         * Factory-created order has no initial checkout
         * histories. The processing lifecycle writes:
         *
         * 1 Pending -> Confirmed
         * 2 Confirmed -> Processing
         * 3 Unfulfilled -> Processing
         * 4 Processing -> Shipped
         * 5 Shipped -> Delivered
         * 6 Processing -> Completed
         */
        $this->assertDatabaseCount(
            'order_status_histories',
            6,
        );
    }

    public function test_admin_transition_records_admin_actor_and_note(): void
    {
        $admin = $this->createAdmin();

        $order = $this->pendingOrder();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.confirm',
                    $order,
                ),
                [
                    'note' => 'Customer details verified.',
                ],
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => OrderStatus::Pending
                    ->value,

                'to_status' => OrderStatus::Confirmed
                    ->value,

                'actor_type' => $admin->getMorphClass(),

                'actor_id' => $admin->id,

                'note' => 'Customer details verified.',
            ],
        );
    }

    public function test_admin_cannot_start_processing_from_pending_order(): void
    {
        $admin = $this->createAdmin();

        $order = $this->pendingOrder();

        $showUrl = route(
            'admin.orders.show',
            $order,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                $showUrl,
            )
            ->post(
                route(
                    'admin.orders.start-processing',
                    $order,
                ),
            )
            ->assertRedirect(
                $showUrl,
            )
            ->assertSessionHasErrors(
                'status',
            );

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );

        $this->assertDatabaseCount(
            'order_status_histories',
            0,
        );
    }

    public function test_order_show_exposes_only_valid_next_action(): void
    {
        $admin = $this->createAdmin();

        $order = $this->pendingOrder();

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
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->component(
                        'Admin/Orders/Show',
                    )
                    ->has(
                        'order.available_actions',
                        1,
                    )
                    ->where(
                        'order.available_actions.0.key',
                        'confirm',
                    )
                    ->where(
                        'order.available_actions.0.label',
                        'Confirm Order',
                    ),
            );
    }

    private function pendingOrder(): Order
    {
        $customer = User::factory()
            ->create();

        return Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()
            ->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);
    }
}
