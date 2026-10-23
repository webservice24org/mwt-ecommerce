<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class AdminOrderLifecycleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_process_order_through_complete_http_lifecycle(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();

        $customer = User::factory()
            ->create();

        $order = Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        /*
         * Pending → Confirmed
         */
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
                    'note' => 'Confirmed by integration test.',
                ],
            )
            ->assertSessionHasNoErrors()
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

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );

        /*
         * Confirmation itself must not issue invoice.
         */
        $this->assertDatabaseMissing(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );

        /*
         * Confirmed → Processing
         */
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
                    'note' => 'Warehouse processing started.',
                ],
            )
            ->assertSessionHasNoErrors()
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

        /*
         * Starting processing must issue the single
         * immutable invoice.
         */
        $this->assertDatabaseHas(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );

        $this->assertSame(
            1,
            $order
                ->invoice()
                ->count(),
        );

        $invoiceNumber = $order
            ->invoice()
            ->firstOrFail()
            ->invoice_number;

        /*
         * Processing → Shipped
         */
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
                    'note' => 'Shipment dispatched.',
                ],
            )
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order->fulfilment_status,
        );

        /*
         * Shipped → Delivered
         */
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
                [
                    'note' => 'Customer received order.',
                ],
            )
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order->fulfilment_status,
        );

        /*
         * Delivered → Completed
         */
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
                [
                    'note' => 'Order closed.',
                ],
            )
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame(
            OrderStatus::Completed,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order->fulfilment_status,
        );

        /*
         * Lifecycle transitions must not produce
         * another invoice.
         */
        $this->assertSame(
            1,
            $order
                ->invoice()
                ->count(),
        );

        $this->assertSame(
            $invoiceNumber,
            $order
                ->invoice()
                ->firstOrFail()
                ->invoice_number,
        );
    }

    public function test_http_lifecycle_cannot_skip_required_order_states(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();

        $customer = User::factory()
            ->create();

        $order = Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        /*
         * Pending cannot jump directly to shipped.
         */
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
                    'note' => 'Invalid direct shipment.',
                ],
            )
            ->assertSessionHasErrors();

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );

        /*
         * Pending cannot jump directly to delivery.
         */
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
                [
                    'note' => 'Invalid direct delivery.',
                ],
            )
            ->assertSessionHasErrors();

        /*
         * Pending cannot complete directly.
         */
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
                [
                    'note' => 'Invalid direct completion.',
                ],
            )
            ->assertSessionHasErrors();

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );

        $this->assertDatabaseMissing(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );
    }

    public function test_repeated_http_transition_does_not_duplicate_effects(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();

        $order = Order::factory()
            ->for(
                User::factory(),
            )
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

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
            )
            ->assertSessionHasNoErrors();

        $historyCount = $order
            ->statusHistories()
            ->count();

        /*
         * Second confirmation must fail.
         */
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
            )
            ->assertSessionHasErrors();

        $order->refresh();

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->status,
        );

        $this->assertSame(
            $historyCount,
            $order
                ->statusHistories()
                ->count(),
        );
    }

    public function test_guest_cannot_access_admin_order_processing_routes(): void
    {
        $order = Order::factory()
            ->for(
                User::factory(),
            )
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $this
            ->get(
                route(
                    'admin.orders.show',
                    $order,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );

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

    public function test_customer_web_session_cannot_be_used_as_admin_authentication(): void
    {
        $customer = User::factory()
            ->create();

        $order = Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        /*
         * Customer is authenticated on the normal web
         * guard, not the admin guard.
         */
        $this
            ->actingAs(
                $customer,
            )
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

    private function createAdmin(): Admin
    {
        return Admin::factory()
            ->create([
                'role' => AdminRole::Admin,
                'is_active' => true,
            ]);
    }
}
