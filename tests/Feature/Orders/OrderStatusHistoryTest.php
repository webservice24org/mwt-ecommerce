<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_store_status_history(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $history = $order
            ->statusHistories()
            ->create([
                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => OrderStatus::Pending
                    ->value,

                'to_status' => OrderStatus::Confirmed
                    ->value,

                'actor_type' => User::class,

                'actor_id' => $user->id,

                'note' => 'Order confirmed.',

                'metadata' => [
                    'source' => 'test',
                ],

                'changed_at' => now(),
            ]);

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'id' => $history->id,

                'order_id' => $order->id,

                'type' => 'order',

                'from_status' => 'pending',

                'to_status' => 'confirmed',

                'actor_type' => User::class,

                'actor_id' => $user->id,
            ],
        );

        $this->assertCount(
            1,
            $order
                ->fresh()
                ->statusHistories,
        );

        $this->assertSame(
            [
                'source' => 'test',
            ],
            $history->metadata,
        );
    }

    public function test_history_can_be_recorded_without_actor_for_system_changes(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $history = $order
            ->statusHistories()
            ->create([
                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => null,

                'to_status' => OrderStatus::Pending
                    ->value,

                'changed_at' => now(),
            ]);

        $this->assertNull(
            $history->actor_type,
        );

        $this->assertNull(
            $history->actor_id,
        );
    }
}
