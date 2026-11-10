<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Orders\Actions\ShipOrderAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Queries\OrderFullyShippedQuery;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ShipShipmentAction
{
    public function __construct(
        private OrderFullyShippedQuery $orderFullyShipped,
        private ShipOrderAction $shipOrder,
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
         *     should_ship_order: bool
         * } $result
         */
        $result = DB::transaction(
            function () use ($shipment): array {
                /*
                 * Lock ordering remains consistent with the
                 * preceding shipment actions:
                 *
                 * Order first.
                 * Shipment second.
                 *
                 * This serializes physical fulfilment work
                 * for one order and prevents concurrent
                 * shipment operations from racing the
                 * aggregate-completion decision.
                 */
                $lockedOrder = Order::query()
                    ->whereKey(
                        $shipment->order_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedOrder->status !==
                    OrderStatus::Processing
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Only processing orders can ship physical shipments.',
                    ]);
                }

                if (
                    $lockedOrder
                        ->fulfilment_status !==
                    FulfilmentStatus::Processing
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => 'Order fulfilment must be processing before a shipment can be shipped.',
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
                            ShipmentStatus::Shipped,
                        )
                ) {
                    throw ValidationException::withMessages([
                        'shipment_status' => sprintf(
                            'Shipment cannot transition from %s to %s.',
                            $fromStatus->label(),
                            ShipmentStatus::Shipped
                                ->label(),
                        ),
                    ]);
                }

                $lockedShipment->status =
                    ShipmentStatus::Shipped;

                $lockedShipment->shipped_at =
                    now();

                $lockedShipment->save();

                /*
                 * This decision is made while the parent
                 * order row remains locked.
                 *
                 * CreateShipmentAction and the shipment
                 * lifecycle actions use the same parent
                 * order lock, therefore allocation and
                 * dispatch cannot race each other here.
                 */
                $shouldShipOrder =
                    $this
                        ->orderFullyShipped
                        ->check(
                            $lockedOrder,
                        );

                return [
                    'shipment' => $lockedShipment
                        ->refresh(),

                    'should_ship_order' => $shouldShipOrder,
                ];
            },
        );

        /*
         * Keep the established Phase 7 aggregate action
         * authoritative for:
         *
         * - FulfilmentStatus::Shipped
         * - order status history
         * - existing shipment/order notifications
         *
         * Do not duplicate those responsibilities in the
         * physical Shipment action.
         */
        if (
            $result[
                'should_ship_order'
            ]
        ) {
            $order = Order::query()
                ->whereKey(
                    $result[
                        'shipment'
                    ]->order_id,
                )
                ->firstOrFail();

            /*
             * Normally this is still Processing.
             *
             * This check also makes reconciliation tolerant
             * if another legitimate aggregate transition
             * completed in the small interval after the
             * physical shipment transaction committed.
             */
            if (
                $order->fulfilment_status ===
                FulfilmentStatus::Processing
            ) {
                try {
                    $this
                        ->shipOrder
                        ->execute(
                            order: $order,

                            actor: $actor,

                            note: $note ??
                            'All order quantities have been shipped.',
                        );
                } catch (
                    ValidationException $exception
                ) {
                    $order->refresh();

                    /*
                     * If another process legitimately moved
                     * the aggregate state through Shipped
                     * already, reconciliation is complete.
                     *
                     * Any other failure must still surface.
                     */
                    if (
                        ! in_array(
                            $order
                                ->fulfilment_status,
                            [
                                FulfilmentStatus::Shipped,
                                FulfilmentStatus::Delivered,
                                FulfilmentStatus::Returned,
                            ],
                            true,
                        )
                    ) {
                        throw $exception;
                    }
                }
            }
        }

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
