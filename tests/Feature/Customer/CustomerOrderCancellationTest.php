<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_cancel_customer_order(): void
    {
        $customer = User::factory()
            ->create();

        $order = $this->orderFor(
            $customer,
        );

        $this
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Ordered by mistake.',
                ],
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );

        $this->assertSame(
            OrderStatus::Pending,
            $order
                ->fresh()
                ->status,
        );
    }

    public function test_customer_can_cancel_own_pending_unfulfilled_order(): void
    {
        $customer = User::factory()
            ->create();

        $order = $this->orderFor(
            $customer,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Ordered by mistake.',
                ],
            )
            ->assertRedirect(
                route(
                    'customer.orders.show',
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

                'from_status' => OrderStatus::Pending->value,

                'to_status' => OrderStatus::Cancelled->value,

                'actor_type' => $customer->getMorphClass(),

                'actor_id' => $customer->id,

                'note' => 'Ordered by mistake.',
            ],
        );
    }

    public function test_customer_cancellation_requires_reason(): void
    {
        $customer = User::factory()
            ->create();

        $order = $this->orderFor(
            $customer,
        );

        $showUrl = route(
            'customer.orders.show',
            $order,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->from(
                $showUrl,
            )
            ->post(
                route(
                    'customer.orders.cancel',
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

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $owner = User::factory()
            ->create();

        $attacker = User::factory()
            ->create();

        $order = $this->orderFor(
            $owner,
        );

        $this
            ->actingAs(
                $attacker,
            )
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Attempted cancellation.',
                ],
            )
            ->assertNotFound();

        $this->assertSame(
            OrderStatus::Pending,
            $order
                ->fresh()
                ->status,
        );

        $this->assertDatabaseMissing(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'to_status' => OrderStatus::Cancelled->value,
            ],
        );
    }

    public function test_customer_cannot_cancel_confirmed_order(): void
    {
        $customer = User::factory()
            ->create();

        $order = $this->orderFor(
            customer: $customer,
            status: OrderStatus::Confirmed,
        );

        $showUrl = route(
            'customer.orders.show',
            $order,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->from(
                $showUrl,
            )
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Please cancel.',
                ],
            )
            ->assertRedirect(
                $showUrl,
            )
            ->assertSessionHasErrors(
                'cancellation',
            );

        $this->assertSame(
            OrderStatus::Confirmed,
            $order
                ->fresh()
                ->status,
        );
    }

    public function test_customer_cannot_cancel_processing_order(): void
    {
        $customer = User::factory()
            ->create();

        $order = $this->orderFor(
            customer: $customer,
            status: OrderStatus::Processing,
            fulfilmentStatus: FulfilmentStatus::Processing,
        );

        $showUrl = route(
            'customer.orders.show',
            $order,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->from(
                $showUrl,
            )
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Please cancel.',
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

    public function test_customer_cancellation_restores_stock_exactly_once(): void
    {
        $customer = User::factory()
            ->create();

        $product = Product::factory()
            ->create([
                'type' => ProductType::Simple,

                'track_inventory' => true,

                'stock_quantity' => 7,
            ]);

        $order = $this->orderFor(
            $customer,
        );

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => $product->name,

                'sku' => $product->sku,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 3,

                'stock_was_decremented' => true,

                'line_total' => 30000,
            ]);

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Ordered accidentally.',
                ],
            )
            ->assertRedirect();

        $this->assertSame(
            10,
            $product
                ->fresh()
                ->stock_quantity,
        );

        /*
         * Direct repeated POST must not restore again.
         */
        $this
            ->actingAs(
                $customer,
            )
            ->from(
                route(
                    'customer.orders.show',
                    $order,
                ),
            )
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Try again.',
                ],
            )
            ->assertSessionHasErrors(
                'cancellation',
            );

        $this->assertSame(
            10,
            $product
                ->fresh()
                ->stock_quantity,
        );

        $this->assertSame(
            1,
            $order
                ->statusHistories()
                ->where(
                    'to_status',
                    OrderStatus::Cancelled->value,
                )
                ->count(),
        );
    }

    public function test_order_show_exposes_customer_cancellation_only_when_safe(): void
    {
        $customer = User::factory()
            ->create();

        $pending = $this->orderFor(
            $customer,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.orders.show',
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

        $confirmed = $this->orderFor(
            customer: $customer,
            status: OrderStatus::Confirmed,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.orders.show',
                    $confirmed,
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

    private function orderFor(
        User $customer,
        OrderStatus $status = OrderStatus::Pending,
        FulfilmentStatus $fulfilmentStatus =
        FulfilmentStatus::Unfulfilled,
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => $status,

                'fulfilment_status' => $fulfilmentStatus,
            ]);
    }
}
