<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MarkOrderPaymentDueAction
{
    public function execute(
        Order $order,
        Carbon $paymentDueAt,
        ?Model $actor = null,
        ?string $note = null,
    ): Order {
        return DB::transaction(
            function () use ($order, $paymentDueAt, $actor, $note): Order {
                $lockedOrder =
                    Order::query()
                        ->whereKey(
                            $order->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * Cash on Delivery is currently the
                 * storefront's only payment method,
                 * therefore no redundant enum comparison
                 * is needed here.
                 */

                if (
                    $lockedOrder
                        ->fulfilment_status !==
                    FulfilmentStatus::Delivered
                ) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'The order must be delivered before COD payment can be marked as due.',
                    ]);
                }

                $fromStatus =
                    $lockedOrder
                        ->payment_status;

                if (
                    $fromStatus !==
                    PaymentStatus::Unpaid
                ) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'Only an unpaid COD order can be marked as due.',
                    ]);
                }

                $changedAt =
                    now();

                $lockedOrder
                    ->payment_status =
                    PaymentStatus::Due;

                $lockedOrder
                    ->payment_due_at =
                    $paymentDueAt;

                $lockedOrder
                    ->paid_at =
                    null;

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'type' => OrderStatusHistory::TYPE_PAYMENT,

                        'from_status' => $fromStatus
                            ->value,

                        'to_status' => PaymentStatus::Due
                            ->value,

                        'actor_type' => $actor
                            ?->getMorphClass(),

                        'actor_id' => $actor
                            ?->getKey(),

                        'note' => $note ??
                            'COD payment remains due.',

                        'metadata' => [
                            'payment_due_at' => $paymentDueAt
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
