<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Queries\GetOrderStatusTimelineQuery;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetOrderStatusTimelineQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_returns_normalized_status_labels_in_order(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $firstTime =
            now()->subMinutes(3);

        $secondTime =
            now()->subMinutes(2);

        $thirdTime =
            now()->subMinute();

        $order
            ->statusHistories()
            ->create([
                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => null,

                'to_status' => OrderStatus::Pending
                    ->value,

                'note' => 'Order placed.',

                'changed_at' => $firstTime,
            ]);

        $order
            ->statusHistories()
            ->create([
                'type' => OrderStatusHistory::TYPE_PAYMENT,

                'from_status' => null,

                'to_status' => PaymentStatus::Unpaid
                    ->value,

                'note' => 'Payment awaiting collection.',

                'changed_at' => $secondTime,
            ]);

        $order
            ->statusHistories()
            ->create([
                'type' => OrderStatusHistory::TYPE_FULFILMENT,

                'from_status' => FulfilmentStatus::Unfulfilled
                    ->value,

                'to_status' => FulfilmentStatus::Processing
                    ->value,

                'note' => 'Processing started.',

                'changed_at' => $thirdTime,
            ]);

        $timeline = app(
            GetOrderStatusTimelineQuery::class,
        )->get(
            $order,
        );

        $this->assertCount(
            3,
            $timeline,
        );

        $this->assertSame(
            'order',
            $timeline[0]['type'],
        );

        $this->assertSame(
            'Order',
            $timeline[0]['type_label'],
        );

        $this->assertNull(
            $timeline[0]['from'],
        );

        $this->assertSame(
            [
                'value' => 'pending',

                'label' => 'Pending',
            ],
            $timeline[0]['to'],
        );

        $this->assertSame(
            'Payment',
            $timeline[1]['type_label'],
        );

        $this->assertSame(
            'Unpaid',
            $timeline[1]['to']['label'],
        );

        $this->assertSame(
            'Fulfilment',
            $timeline[2]['type_label'],
        );

        $this->assertSame(
            [
                'value' => 'unfulfilled',

                'label' => 'Unfulfilled',
            ],
            $timeline[2]['from'],
        );

        $this->assertSame(
            [
                'value' => 'processing',

                'label' => 'Processing',
            ],
            $timeline[2]['to'],
        );

        $this->assertSame(
            'Processing started.',
            $timeline[2]['note'],
        );
    }
}
