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

final readonly class ReturnOrderAction
{
    /**
     * @throws ValidationException
     */
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

                /*
                 * An order may be returned:
                 *
                 * - during fulfilment while OrderStatus is
                 *   still Processing;
                 *
                 * - after business completion.
                 *
                 * Order status and fulfilment status remain
                 * independent.
                 */
                if (
                    ! in_array(
                        $lockedOrder->status,
                        [
                            OrderStatus::Processing,
                            OrderStatus::Completed,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Only fulfilled or completed orders can be returned.',
                    ]);
                }

                $fromStatus =
                    $lockedOrder
                        ->fulfilment_status;

                /*
                 * Processing is allowed for a complete
                 * return-to-sender scenario where all
                 * parcels returned before the aggregate
                 * order reached Shipped.
                 */
                if (
                    ! in_array(
                        $fromStatus,
                        [
                            FulfilmentStatus::Processing,
                            FulfilmentStatus::Shipped,
                            FulfilmentStatus::Delivered,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => sprintf(
                            'Fulfilment cannot transition from %s to %s.',
                            $fromStatus->label(),
                            FulfilmentStatus::Returned
                                ->label(),
                        ),
                    ]);
                }

                $lockedOrder->fulfilment_status =
                    FulfilmentStatus::Returned;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_FULFILMENT,

                        'from_status' => $fromStatus->value,

                        'to_status' => FulfilmentStatus::Returned
                            ->value,

                        'actor_type' => $actor
                            ?->getMorphClass(),

                        'actor_id' => $actor
                            ?->getKey(),

                        'note' => $note,

                        'changed_at' => now(),
                    ]);

                return $lockedOrder
                    ->refresh();
            },
        );
    }
}
