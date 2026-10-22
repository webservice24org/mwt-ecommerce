<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CancelOrderAction
{
    public function __construct(
        private RestoreCancelledOrderStockAction $restoreStock,
    ) {}

    /**
     * Admin / internal cancellation.
     *
     * @throws ValidationException
     */
    public function execute(
        Order $order,
        ?Model $actor = null,
        ?string $reason = null,
    ): Order {
        return $this->cancel(
            order: $order,
            actor: $actor,
            reason: $reason,
            customerInitiated: false,
        );
    }

    /**
     * Customer self-cancellation.
     *
     * Customer rules are deliberately stricter than the
     * general/admin order transition contract.
     *
     * @throws ValidationException
     */
    public function executeForCustomer(
        Order $order,
        User $customer,
        string $reason,
    ): Order {
        return $this->cancel(
            order: $order,
            actor: $customer,
            reason: $reason,
            customerInitiated: true,
        );
    }

    /**
     * @throws ValidationException
     */
    private function cancel(
        Order $order,
        ?Model $actor,
        ?string $reason,
        bool $customerInitiated,
    ): Order {
        return DB::transaction(
            function () use ($order, $actor, $reason, $customerInitiated): Order {
                $lockedOrder = Order::query()
                    ->whereKey(
                        $order->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $fromStatus =
                    $lockedOrder->status;

                /*
                 * Customer cancellation must still be in the
                 * untouched pre-fulfilment state.
                 *
                 * This is checked AFTER the row lock so an
                 * order cannot start processing concurrently
                 * while the customer is cancelling it.
                 */
                if (
                    $customerInitiated
                    && (
                        $lockedOrder->status !==
                        OrderStatus::Pending
                        || $lockedOrder->fulfilment_status !==
                        FulfilmentStatus::Unfulfilled
                    )
                ) {
                    throw ValidationException::withMessages([
                        'cancellation' => 'This order can no longer be cancelled directly. Please contact support if you need assistance.',
                    ]);
                }

                if (
                    ! $fromStatus->canTransitionTo(
                        OrderStatus::Cancelled,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'cancellation' => sprintf(
                            'Order cannot be cancelled from %s status.',
                            $fromStatus->label(),
                        ),
                    ]);
                }

                /*
                 * Restore exactly the inventory that was
                 * deducted when this order was created.
                 */
                $this
                    ->restoreStock
                    ->execute(
                        $lockedOrder,
                    );

                /*
                 * Payment and fulfilment states remain
                 * independent.
                 */
                $lockedOrder->status =
                    OrderStatus::Cancelled;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_ORDER,

                        'from_status' => $fromStatus->value,

                        'to_status' => OrderStatus::Cancelled->value,

                        'actor_type' => $actor
                            ?->getMorphClass(),

                        'actor_id' => $actor
                            ?->getKey(),

                        'note' => $reason,

                        'changed_at' => now(),
                    ]);

                return $lockedOrder
                    ->refresh();
            },
        );
    }
}
