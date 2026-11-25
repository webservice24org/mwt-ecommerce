<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Services\OrderTrashLedger;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManageOrderTrashAction
{
    public function trash(Order $order, Admin $admin): void
    {
        $this->authorize($admin);

        DB::transaction(function () use ($order, $admin): void {
            $locked = $this->lockOrder($order);
            if ($locked->trashed_at !== null) {
                return;
            }

            // Active fulfilment cannot silently disappear from the admin list.
            if (! $locked->status->isTerminal()) {
                $this->reject('Cancel or complete the order through its normal workflow before moving it to Trash.');
            }

            $locked->trashed_at = now();
            $locked->save();
            OrderTrashLedger::record($locked, $admin, 'trashed');
        });
    }

    public function restore(Order $order, Admin $admin): void
    {
        $this->authorize($admin);

        DB::transaction(function () use ($order, $admin): void {
            $locked = $this->lockOrder($order);
            if ($locked->trashed_at === null) {
                return;
            }

            $locked->trashed_at = null;
            $locked->save();
            OrderTrashLedger::record($locked, $admin, 'restored');
        });
    }

    public static function meetsBasicPurgeRequirements(Order $order): bool
    {
        return $order->trashed_at !== null
            && $order->status === OrderStatus::Cancelled
            && $order->payment_status === PaymentStatus::Unpaid
            && $order->fulfilment_status === FulfilmentStatus::Unfulfilled
            && $order->paid_at === null
            && $order->payment_due_at === null
            && $order->discount_total === 0;
    }

    public function purge(Order $order, Admin $admin): void
    {
        $this->authorize($admin);

        DB::transaction(function () use ($order, $admin): void {
            // Same lock order as checkout: customer first. A deleted checkout
            // token cannot be reused while the tombstone is being committed.
            User::query()->whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $locked = $this->lockOrder($order);

            if (! self::meetsBasicPurgeRequirements($locked)) {
                $this->reject('Only trashed, cancelled, unpaid and unfulfilled orders without discounts may be permanently deleted.');
            }

            if ($locked->invoice()->exists()
                || $locked->shipments()->exists()
                || $locked->promoCodeUsages()->exists()
                || $locked->verifiedReviews()->exists()) {
                $this->reject('This order has protected invoice, shipment, promotion or review records. Restore it or keep it in Trash.');
            }

            if ($locked->statusHistories()->where('type', OrderStatusHistory::TYPE_PAYMENT)
                ->where(function ($query): void {
                    $states = [PaymentStatus::Paid->value, PaymentStatus::Due->value, PaymentStatus::Pending->value, PaymentStatus::Refunded->value, PaymentStatus::PartiallyRefunded->value];
                    $query->whereIn('from_status', $states)->orWhereIn('to_status', $states);
                })->exists()) {
                $this->reject('This order has protected payment history.');
            }

            if ($locked->items()->whereNull('stock_was_decremented')->exists()
                || ($locked->items()->where('stock_was_decremented', true)->exists()
                    && ! $locked->statusHistories()->where('type', OrderStatusHistory::TYPE_ORDER)
                        ->where('to_status', OrderStatus::Cancelled->value)->exists())) {
                $this->reject('Inventory history requires manual review before permanent deletion.');
            }

            OrderTrashLedger::record($locked, $admin, 'purged');
            // Items and status history cascade; protected relations above do not.
            // Never restock or change payment state as part of deletion.
            $locked->delete();
        });
    }

    private function lockOrder(Order $order): Order
    {
        return Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
    }

    private function authorize(Admin $admin): void
    {
        abort_unless($admin->is_active && $admin->isSuperAdmin(), 403);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['order_trash' => $message]);
    }
}
