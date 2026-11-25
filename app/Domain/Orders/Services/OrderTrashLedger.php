<?php

declare(strict_types=1);

namespace App\Domain\Orders\Services;

use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderTrashLedger
{
    public static function fingerprint(int $userId, string $token): string
    {
        // The checkout token is a server-generated UUID. Normalize case for
        // consistency with the case-insensitive MySQL unique key.
        return hash('sha256', $userId.':'.strtolower(rtrim($token)));
    }

    public static function assertSubmissionAvailable(int $userId, string $token, bool $locked = false): void
    {
        $query = DB::table('order_trash_events')
            ->where('submission_fingerprint', self::fingerprint($userId, $token));

        $exists = $locked
            ? $query->lockForUpdate()->first(['id']) !== null
            : $query->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'order_submission' => 'This submission belongs to a permanently deleted order. Review your cart and submit a new checkout.',
            ]);
        }
    }

    public static function record(Order $order, Admin $admin, string $action): void
    {
        DB::table('order_trash_events')->insert([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'admin_id' => $admin->id,
            'action' => $action,
            'submission_fingerprint' => $action === 'purged' && $order->idempotency_key !== null
                ? self::fingerprint($order->user_id, $order->idempotency_key)
                : null,
            'occurred_at' => now(),
        ]);
    }
}
