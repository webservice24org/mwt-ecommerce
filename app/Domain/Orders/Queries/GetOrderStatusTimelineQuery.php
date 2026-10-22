<?php

declare(strict_types=1);

namespace App\Domain\Orders\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;

final readonly class GetOrderStatusTimelineQuery
{
    /**
     * @return list<array{
     *     id: int,
     *     type: string,
     *     type_label: string,
     *     from: array{
     *         value: string,
     *         label: string
     *     }|null,
     *     to: array{
     *         value: string,
     *         label: string
     *     },
     *     note: string|null,
     *     changed_at: string
     * }>
     */
    public function get(
        Order $order,
    ): array {
        $histories = $order
            ->statusHistories()
            ->orderBy(
                'changed_at',
            )
            ->orderBy(
                'id',
            )
            ->get();

        $timeline = [];

        foreach ($histories as $history) {
            $timeline[] = [
                'id' => (int) $history->id,

                'type' => $history->type,

                'type_label' => $this->typeLabel(
                    $history->type,
                ),

                'from' => $history->from_status === null
                    ? null
                    : [
                        'value' => $history
                            ->from_status,

                        'label' => $this->statusLabel(
                            $history->type,
                            $history->from_status,
                        ),
                    ],

                'to' => [
                    'value' => $history->to_status,

                    'label' => $this->statusLabel(
                        $history->type,
                        $history->to_status,
                    ),
                ],

                'note' => $history->note,

                'changed_at' => $history
                    ->changed_at
                    ->toIso8601String(),
            ];
        }

        return $timeline;
    }

    private function typeLabel(
        string $type,
    ): string {
        return match ($type) {
            OrderStatusHistory::TYPE_ORDER => 'Order',

            OrderStatusHistory::TYPE_PAYMENT => 'Payment',

            OrderStatusHistory::TYPE_FULFILMENT => 'Fulfilment',

            default => 'Status',
        };
    }

    private function statusLabel(
        string $type,
        string $status,
    ): string {
        return match ($type) {
            OrderStatusHistory::TYPE_ORDER => OrderStatus::from(
                $status,
            )->label(),

            OrderStatusHistory::TYPE_PAYMENT => PaymentStatus::from(
                $status,
            )->label(),

            OrderStatusHistory::TYPE_FULFILMENT => FulfilmentStatus::from(
                $status,
            )->label(),

            default => $status,
        };
    }
}
