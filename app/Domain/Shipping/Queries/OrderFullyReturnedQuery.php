<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Queries;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class OrderFullyReturnedQuery
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

        if ($orderItems->isEmpty()) {
            return false;
        }

        $orderItemIds = $orderItems
            ->pluck(
                'id',
            )
            ->all();

        $returnedQuantities = DB::table(
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
                ShipmentStatus::Returned
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
                'SUM(shipment_items.quantity) AS returned_quantity',
            )
            ->groupBy(
                'shipment_items.order_item_id',
            )
            ->pluck(
                'returned_quantity',
                'order_item_id',
            );

        foreach ($orderItems as $orderItem) {
            $returnedQuantity = (int) (
                $returnedQuantities->get(
                    $orderItem->id,
                ) ?? 0
            );

            /*
             * Require exact equality.
             *
             * Less than ordered:
             *     partial return.
             *
             * Greater than ordered:
             *     corrupted / over-returned data.
             */
            if (
                $returnedQuantity !==
                $orderItem->quantity
            ) {
                return false;
            }
        }

        return true;
    }
}
