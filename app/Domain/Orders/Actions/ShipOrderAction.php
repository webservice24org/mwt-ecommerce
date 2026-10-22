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

final readonly class ShipOrderAction
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

                if (
                    $lockedOrder->status !==
                    OrderStatus::Processing
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Only processing orders can be shipped.',
                    ]);
                }

                $fromStatus =
                    $lockedOrder
                        ->fulfilment_status;

                if (
                    ! $fromStatus->canTransitionTo(
                        FulfilmentStatus::Shipped,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => sprintf(
                            'Fulfilment cannot transition from %s to %s.',
                            $fromStatus->label(),
                            FulfilmentStatus::Shipped->label(),
                        ),
                    ]);
                }

                $lockedOrder->fulfilment_status =
                    FulfilmentStatus::Shipped;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_FULFILMENT,

                        'from_status' => $fromStatus->value,

                        'to_status' => FulfilmentStatus::Shipped
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
