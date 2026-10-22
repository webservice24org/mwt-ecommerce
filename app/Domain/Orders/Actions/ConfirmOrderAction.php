<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ConfirmOrderAction
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

                $fromStatus =
                    $lockedOrder->status;

                if (
                    ! $fromStatus->canTransitionTo(
                        OrderStatus::Confirmed,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'status' => sprintf(
                            'Order cannot transition from %s to %s.',
                            $fromStatus->label(),
                            OrderStatus::Confirmed->label(),
                        ),
                    ]);
                }

                $lockedOrder->status =
                    OrderStatus::Confirmed;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_ORDER,

                        'from_status' => $fromStatus->value,

                        'to_status' => OrderStatus::Confirmed
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
