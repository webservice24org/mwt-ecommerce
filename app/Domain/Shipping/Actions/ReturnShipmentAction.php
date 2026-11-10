<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Orders\Actions\ReturnOrderAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Queries\OrderFullyReturnedQuery;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReturnShipmentAction
{
    public function __construct(
        private OrderFullyReturnedQuery $orderFullyReturned,
        private ReturnOrderAction $returnOrder,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        Shipment $shipment,
        ?Model $actor = null,
        ?string $note = null,
    ): Shipment {
        /**
         * @var array{
         *     shipment: Shipment,
         *     fully_returned: bool
         * } $result
         */
        $result = DB::transaction(
            function () use ($shipment): array {
                $lockedOrder = Order::query()
                    ->whereKey(
                        $shipment->order_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! in_array(
                        $lockedOrder->status,
                        [
                            OrderStatus::Processing,
                            OrderStatus::Completed,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'This order cannot receive shipment return updates.',
                    ]);
                }

                if (
                    ! in_array(
                        $lockedOrder
                            ->fulfilment_status,
                        [
                            FulfilmentStatus::Processing,
                            FulfilmentStatus::Shipped,
                            FulfilmentStatus::Delivered,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => 'This fulfilment state cannot receive shipment returns.',
                    ]);
                }

                $lockedShipment =
                    Shipment::query()
                        ->whereKey(
                            $shipment->getKey(),
                        )
                        ->where(
                            'order_id',
                            $lockedOrder->id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $fromStatus =
                    $lockedShipment->status;

                if (
                    ! $fromStatus
                        ->canTransitionTo(
                            ShipmentStatus::Returned,
                        )
                ) {
                    throw ValidationException::withMessages([
                        'shipment_status' => sprintf(
                            'Shipment cannot transition from %s to %s.',
                            $fromStatus->label(),
                            ShipmentStatus::Returned
                                ->label(),
                        ),
                    ]);
                }

                $lockedShipment->status =
                    ShipmentStatus::Returned;

                $lockedShipment->returned_at =
                    now();

                $lockedShipment->save();

                $fullyReturned =
                    $this
                        ->orderFullyReturned
                        ->check(
                            $lockedOrder,
                        );

                return [
                    'shipment' => $lockedShipment
                        ->refresh(),

                    'fully_returned' => $fullyReturned,
                ];
            },
        );

        if (
            $result[
                'fully_returned'
            ]
        ) {
            $order = Order::query()
                ->whereKey(
                    $result[
                        'shipment'
                    ]->order_id,
                )
                ->firstOrFail();

            if (
                $order->fulfilment_status !==
                FulfilmentStatus::Returned
            ) {
                try {
                    $this
                        ->returnOrder
                        ->execute(
                            order: $order,

                            actor: $actor,

                            note: $note ??
                            'All order quantities have been returned.',
                        );
                } catch (
                    ValidationException $exception
                ) {
                    $order->refresh();

                    /*
                     * Another reconciliation may have
                     * completed the aggregate transition.
                     */
                    if (
                        $order
                            ->fulfilment_status !==
                        FulfilmentStatus::Returned
                    ) {
                        throw $exception;
                    }
                }
            }
        }

        /*
         * IMPORTANT:
         *
         * Returned does not mean Restocked.
         *
         * Returned inventory may still require inspection,
         * quarantine, damage handling, refund approval or
         * another inventory adjustment workflow.
         */
        return $result[
        'shipment'
        ]
            ->refresh()
            ->load([
                'order',
                'shippingMethod',
                'items',
                'items.orderItem',
            ]);
    }
}
