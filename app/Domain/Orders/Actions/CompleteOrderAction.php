<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CompleteOrderAction
{
    public function execute(
        Order $order,
        ?Model $actor = null,
        ?string $note = null,
    ): Order {
        return DB::transaction(
            function () use ($order, $actor, $note): Order {
                $lockedOrder = Order::query()
                    ->whereKey(
                        $order->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $fromOrderStatus =
                    $lockedOrder->status;

                if (
                    ! $fromOrderStatus->canTransitionTo(
                        OrderStatus::Completed,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'status' => sprintf(
                            'Order cannot transition from %s to %s.',
                            $fromOrderStatus->label(),
                            OrderStatus::Completed->label(),
                        ),
                    ]);
                }

                /*
                 * Completing the commercial order should only
                 * happen after physical fulfilment has reached
                 * Delivered.
                 */
                if (
                    $lockedOrder->fulfilment_status !==
                    FulfilmentStatus::Delivered
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => 'Order cannot be completed before fulfilment is delivered.',
                    ]);
                }

                $lockedOrder->status =
                    OrderStatus::Completed;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_ORDER,

                        'from_status' => $fromOrderStatus->value,

                        'to_status' => OrderStatus::Completed
                            ->value,

                        'actor_type' => $actor?->getMorphClass(),

                        'actor_id' => $actor?->getKey(),

                        'note' => $note,

                        'changed_at' => now(),
                    ]);

                return $lockedOrder->refresh();
            },
        );
    }
}
