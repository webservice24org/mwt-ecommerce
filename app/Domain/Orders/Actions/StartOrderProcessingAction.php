<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Mail\Actions\SendOrderStatusNotificationAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StartOrderProcessingAction
{
    public function __construct(
        private IssueInvoiceAction $issueInvoice,
        private SendOrderStatusNotificationAction $sendOrderStatusNotification,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        Order $order,
        ?Model $actor = null,
        ?string $note = null,
    ): Order {
        $processingOrder = DB::transaction(
            function () use ($order, $actor, $note): Order {
                $lockedOrder = Order::query()
                    ->whereKey(
                        $order->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $fromOrderStatus =
                    $lockedOrder->status;

                $fromFulfilmentStatus =
                    $lockedOrder->fulfilment_status;

                if (
                    ! $fromOrderStatus->canTransitionTo(
                        OrderStatus::Processing,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'status' => sprintf(
                            'Order cannot transition from %s to %s.',
                            $fromOrderStatus->label(),
                            OrderStatus::Processing->label(),
                        ),
                    ]);
                }

                if (
                    ! $fromFulfilmentStatus->canTransitionTo(
                        FulfilmentStatus::Processing,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => sprintf(
                            'Fulfilment cannot transition from %s to %s.',
                            $fromFulfilmentStatus->label(),
                            FulfilmentStatus::Processing->label(),
                        ),
                    ]);
                }

                $changedAt =
                    now();

                $lockedOrder->status =
                    OrderStatus::Processing;

                $lockedOrder->fulfilment_status =
                    FulfilmentStatus::Processing;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_ORDER,

                        'from_status' => $fromOrderStatus->value,

                        'to_status' => OrderStatus::Processing->value,

                        'actor_type' => $actor
                            ?->getMorphClass(),

                        'actor_id' => $actor
                            ?->getKey(),

                        'note' => $note,

                        'changed_at' => $changedAt,
                    ]);

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_FULFILMENT,

                        'from_status' => $fromFulfilmentStatus->value,

                        'to_status' => FulfilmentStatus::Processing->value,

                        'actor_type' => $actor
                            ?->getMorphClass(),

                        'actor_id' => $actor
                            ?->getKey(),

                        'note' => $note,

                        'changed_at' => $changedAt,
                    ]);

                /*
                 * The order is now Processing inside this
                 * transaction, therefore it satisfies the
                 * invoice issuance policy from 7.3B.
                 *
                 * IssueInvoiceAction is idempotent and its
                 * invoice creation participates in this
                 * outer transaction.
                 *
                 * If issuance throws, Laravel rolls back:
                 * - order status
                 * - fulfilment status
                 * - both history rows
                 * - invoice creation
                 */
                $this
                    ->issueInvoice
                    ->execute(
                        $lockedOrder,
                    );

                return $lockedOrder
                    ->refresh();
            },
        );

        $this
            ->sendOrderStatusNotification
            ->processing(
                $processingOrder,
            );

        return $processingOrder;
    }
}
