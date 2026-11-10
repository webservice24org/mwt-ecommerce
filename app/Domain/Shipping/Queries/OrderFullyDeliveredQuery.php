<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Queries;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class OrderFullyDeliveredQuery
{
    public function check(
        Order $order,
    ): bool {
        $orderItems = $order
            ->items()
            ->get([
                'id',
                'quantity',
            ]);

        /*
         * Never consider an empty order physically
         * delivered.
         */
        if ($orderItems->isEmpty()) {
            return false;
        }

        $orderItemIds = $orderItems
            ->pluck(
                'id',
            )
            ->all();

        /*
         * Aggregate only quantities whose CURRENT
         * physical shipment state is Delivered.
         *
         * Returned quantities intentionally do not count.
         *
         * A returned parcel is no longer currently in the
         * delivered state. Return reconciliation will be
         * handled separately in 8.5E.
         */
        $deliveredQuantities = DB::table(
            'shipment_items',
        )
            ->join(
                'shipments',
                'shipments.id',
                '=',
                'shipment_items.shipment_id',
            )
            ->where(
                'shipments.order_id',
                $order->id,
            )
            ->where(
                'shipments.status',
                ShipmentStatus::Delivered
                    ->value,
            )
            ->whereIn(
                'shipment_items.order_item_id',
                $orderItemIds,
            )
            ->selectRaw(
                'shipment_items.order_item_id AS order_item_id',
            )
            ->selectRaw(
                'SUM(shipment_items.quantity) AS delivered_quantity',
            )
            ->groupBy(
                'shipment_items.order_item_id',
            )
            ->pluck(
                'delivered_quantity',
                'order_item_id',
            );

        foreach ($orderItems as $orderItem) {
            $deliveredQuantity = (int) (
                $deliveredQuantities->get(
                    $orderItem->id,
                ) ?? 0
            );

            /*
             * Exact equality is deliberate.
             *
             * Less:
             *     not fully delivered.
             *
             * Greater:
             *     corrupted / over-delivered data must not
             *     silently qualify as complete.
             */
            if (
                $deliveredQuantity !==
                $orderItem->quantity
            ) {
                return false;
            }
        }

        return true;
    }
}
