<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Orders\Actions\DeliverOrderAction;
use App\Domain\Orders\Actions\ShipOrderAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Queries\OrderFullyDeliveredQuery;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DeliverShipmentAction
{
    public function __construct(
        private OrderFullyDeliveredQuery $orderFullyDelivered,
        private ShipOrderAction $shipOrder,
        private DeliverOrderAction $deliverOrder,
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
         *     fully_delivered: bool
         * } $result
         */
        $result = DB::transaction(
            function () use ($shipment): array {
                /*
                 * Maintain the same lock order used by the
                 * rest of the shipment workflow:
                 *
                 * 1. Order
                 * 2. Shipment
                 */
                $lockedOrder = Order::query()
                    ->whereKey(
                        $shipment->order_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * The business order remains Processing
                 * throughout shipment and delivery work.
                 *
                 * OrderStatus::Completed occurs only after
                 * aggregate delivery is complete.
                 */
                if (
                    $lockedOrder->status !==
                    OrderStatus::Processing
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Only processing orders can receive shipment delivery updates.',
                    ]);
                }

                /*
                 * A physical parcel can arrive while another
                 * parcel is still being prepared or shipped.
                 *
                 * Therefore both aggregate states are valid
                 * during physical delivery:
                 *
                 * Processing = not every quantity has shipped
                 * yet.
                 *
                 * Shipped = every quantity has shipped, but
                 * not every quantity has been delivered yet.
                 */
                if (
                    ! in_array(
                        $lockedOrder
                            ->fulfilment_status,
                        [
                            FulfilmentStatus::Processing,
                            FulfilmentStatus::Shipped,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => 'Order fulfilment must be processing or shipped before a shipment can be delivered.',
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
                    $lockedShipment
                        ->status;

                if (
                    ! $fromStatus
                        ->canTransitionTo(
                            ShipmentStatus::Delivered,
                        )
                ) {
                    throw ValidationException::withMessages([
                        'shipment_status' => sprintf(
                            'Shipment cannot transition from %s to %s.',
                            $fromStatus->label(),
                            ShipmentStatus::Delivered
                                ->label(),
                        ),
                    ]);
                }

                $lockedShipment->status =
                    ShipmentStatus::Delivered;

                $lockedShipment->delivered_at =
                    now();

                $lockedShipment->save();

                /*
                 * The reconciliation decision is calculated
                 * while the parent order remains locked.
                 *
                 * Shipment creation, shipment shipping and
                 * shipment delivery all serialize through
                 * the same order lock.
                 */
                $fullyDelivered =
                    $this
                        ->orderFullyDelivered
                        ->check(
                            $lockedOrder,
                        );

                return [
                    'shipment' => $lockedShipment
                        ->refresh(),

                    'fully_delivered' => $fullyDelivered,
                ];
            },
        );

        if (
            $result[
                'fully_delivered'
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
             * Normally a fully delivered order is already
             * aggregate Shipped because 8.5C reconciles
             * shipment dispatch.
             *
             * The Processing branch is a defensive
             * reconciliation path. If all physical
             * quantities are already delivered, they are
             * necessarily also shipped.
             */
            if (
                $order->fulfilment_status ===
                FulfilmentStatus::Processing
            ) {
                $order = $this
                    ->shipOrder
                    ->execute(
                        order: $order,

                        actor: $actor,

                        note: 'All order quantities were shipped before final delivery reconciliation.',
                    );
            }

            if (
                $order->fulfilment_status ===
                FulfilmentStatus::Shipped
            ) {
                try {
                    $this
                        ->deliverOrder
                        ->execute(
                            order: $order,

                            actor: $actor,

                            note: $note ??
                            'All order quantities have been delivered.',
                        );
                } catch (
                    ValidationException $exception
                ) {
                    $order->refresh();

                    /*
                     * Concurrent reconciliation may already
                     * have advanced the aggregate lifecycle.
                     */
                    if (
                        ! in_array(
                            $order
                                ->fulfilment_status,
                            [
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
