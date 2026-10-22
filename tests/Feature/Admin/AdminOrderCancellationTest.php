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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_cannot_cancel_order(): void
    {
        $order = $this->cancellableOrder();

        $this
            ->post(
                route(
                    'admin.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Customer requested cancellation.',
                ],
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );

        $this->assertSame(
            OrderStatus::Pending,
            $order
                ->fresh()
                ->status,
        );
    }

    public function test_admin_cancellation_requires_reason(): void
    {
        $admin = $this->createAdmin();

        $order = $this->cancellableOrder();

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
                    'admin.orders.cancel',
                    $order,
                ),
                [
                    'reason' => '',
                ],
            )
            ->assertRedirect(
                $showUrl,
            )
            ->assertSessionHasErrors(
                'reason',
            );

        $this->assertSame(
            OrderStatus::Pending,
            $order
                ->fresh()
                ->status,
        );
    }

    public function test_admin_can_cancel_pending_order_with_reason(): void
    {
        $admin = $this->createAdmin();

        $order = $this->cancellableOrder();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Duplicate customer order.',
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
            OrderStatus::Cancelled,
            $order->status,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => OrderStatus::Pending
                    ->value,

                'to_status' => OrderStatus::Cancelled
                    ->value,

                'actor_type' => $admin->getMorphClass(),

                'actor_id' => $admin->id,

                'note' => 'Duplicate customer order.',
            ],
        );
    }

    public function test_admin_can_cancel_confirmed_order(): void
    {
        $admin = $this->createAdmin();

        $order = $this->cancellableOrder(
            OrderStatus::Confirmed,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Unable to fulfil this order.',
                ],
            )
            ->assertRedirect(
                route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $this->assertSame(
            OrderStatus::Cancelled,
            $order
                ->fresh()
                ->status,
        );
    }

    public function test_admin_cannot_cancel_processing_order(): void
    {
        $admin = $this->createAdmin();

        $order = $this->cancellableOrder(
            OrderStatus::Processing,
            FulfilmentStatus::Processing,
        );

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
                    'admin.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Attempt invalid cancellation.',
                ],
            )
            ->assertRedirect(
                $showUrl,
            )
            ->assertSessionHasErrors(
                'cancellation',
            );

        $this->assertSame(
            OrderStatus::Processing,
            $order
                ->fresh()
                ->status,
        );
    }

    public function test_order_show_exposes_cancellation_only_when_allowed(): void
    {
        $admin = $this->createAdmin();

        $pending = $this->cancellableOrder();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
                    $pending,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.cancellation.allowed',
                        true,
                    ),
            );

        $processing = $this->cancellableOrder(
            OrderStatus::Processing,
            FulfilmentStatus::Processing,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.orders.show',
                    $processing,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'order.cancellation.allowed',
                        false,
                    ),
            );
    }

    private function cancellableOrder(
        OrderStatus $status = OrderStatus::Pending,
        FulfilmentStatus $fulfilmentStatus =
        FulfilmentStatus::Unfulfilled,
    ): Order {
        $customer = User::factory()
            ->create();

        $order = Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => $status,

                'fulfilment_status' => $fulfilmentStatus,
            ]);

        /*
         * Explicitly non-inventory so these HTTP contract
         * tests are isolated from stock restoration tests.
         */
        $order
            ->items()
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'Admin Cancellation Item',

                'sku' => null,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 1,

                'stock_was_decremented' => false,

                'line_total' => 10000,
            ]);

        return $order;
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
