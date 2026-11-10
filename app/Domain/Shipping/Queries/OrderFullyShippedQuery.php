<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Queries;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class OrderFullyShippedQuery
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
         * An order with no physical order items must never
         * accidentally qualify as fully shipped.
         */
        if ($orderItems->isEmpty()) {
            return false;
        }

        /*
         * These states all prove that the quantity has
         * physically passed through the shipped state.
         *
         * Pending / Processing / Cancelled quantities do
         * not count.
         */
        $shippedStatuses = [
            ShipmentStatus::Shipped
                ->value,

            ShipmentStatus::Delivered
                ->value,

            ShipmentStatus::Returned
                ->value,
        ];

        $orderItemIds = $orderItems
            ->pluck(
                'id',
            )
            ->all();

        /*
         * Aggregate in one query rather than issuing one
         * shipment query for every order line.
         */
        $shippedQuantities = DB::table(
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
            ->whereIn(
                'shipments.status',
                $shippedStatuses,
            )
            ->whereIn(
                'shipment_items.order_item_id',
                $orderItemIds,
            )
            ->selectRaw(
                'shipment_items.order_item_id AS order_item_id',
            )
            ->selectRaw(
                'SUM(shipment_items.quantity) AS shipped_quantity',
            )
            ->groupBy(
                'shipment_items.order_item_id',
            )
            ->pluck(
                'shipped_quantity',
                'order_item_id',
            );

        foreach ($orderItems as $orderItem) {
            $shippedQuantity = (int) (
                $shippedQuantities->get(
                    $orderItem->id,
                ) ?? 0
            );

            /*
             * Equality is intentional.
             *
             * Less than ordered:
             *     not fully shipped.
             *
             * Greater than ordered:
             *     corrupted / over-shipped data must not
             *     silently qualify as complete.
             */
            if (
                $shippedQuantity !==
                $orderItem->quantity
            ) {
                return false;
            }
        }

        return true;
    }
}
