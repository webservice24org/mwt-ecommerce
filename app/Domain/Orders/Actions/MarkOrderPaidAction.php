<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MarkOrderPaidAction
{
    public function execute(
        Order $order,
        ?Model $actor = null,
        ?string $note = null,
    ): Order {
        return DB::transaction(
            function () use ($order, $actor, $note): Order {
                $lockedOrder =
                    Order::query()
                        ->whereKey(
                            $order->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * The current storefront payment contract
                 * supports Cash on Delivery only.
                 *
                 * Do not compare against
                 * PaymentMethod::CashOnDelivery here because
                 * PHPStan correctly knows that it is currently
                 * the enum's only possible payment method.
                 */

                if (
                    $lockedOrder
                        ->fulfilment_status !==
                    FulfilmentStatus::Delivered
                ) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'The order must be delivered before COD payment can be marked as paid.',
                    ]);
                }

                $fromStatus =
                    $lockedOrder
                        ->payment_status;

                if (
                    ! in_array(
                        $fromStatus,
                        [
                            PaymentStatus::Unpaid,
                            PaymentStatus::Due,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'This order cannot currently be marked as paid.',
                    ]);
                }

                $changedAt =
                    now();

                $lockedOrder
                    ->payment_status =
                    PaymentStatus::Paid;

                $lockedOrder
                    ->paid_at =
                    $changedAt;

                /*
                 * Full payment clears any previous
                 * outstanding due date.
                 */
                $lockedOrder
                    ->payment_due_at =
                    null;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_PAYMENT,

                        'from_status' => $fromStatus
                            ->value,

                        'to_status' => PaymentStatus::Paid
                            ->value,

                        'actor_type' => $actor
                            ?->getMorphClass(),

                        'actor_id' => $actor
                            ?->getKey(),

                        'note' => $note ??
                            'COD payment collected in full.',

                        'metadata' => [
                            'paid_at' => $changedAt
                                ->toISOString(),
                        ],

                        'changed_at' => $changedAt,
                    ]);

                return $lockedOrder
                    ->refresh();
            },
        );
    }
}
