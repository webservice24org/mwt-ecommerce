<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Orders\Actions\StartOrderProcessingAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StartShipmentProcessingAction
{
    public function __construct(
        private StartOrderProcessingAction $startOrderProcessing,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        Shipment $shipment,
        ?Model $actor = null,
        ?string $note = null,
    ): Shipment {
        /*
         * First resolve the latest parent-order state.
         *
         * We intentionally do this before the shipment
         * transaction because StartOrderProcessingAction owns:
         *
         * - order transition
         * - fulfilment transition
         * - status histories
         * - invoice issuance
         * - existing notifications
         */
        $shipment->refresh();

        $order = Order::query()
            ->whereKey(
                $shipment->order_id,
            )
            ->firstOrFail();

        /*
         * Pending orders must be confirmed first.
         *
         * We do not silently skip the established
         * Pending -> Confirmed lifecycle.
         */
        if (
            $order->status ===
            OrderStatus::Pending
        ) {
            throw ValidationException::withMessages([
                'status' => 'The order must be confirmed before shipment processing can begin.',
            ]);
        }

        /*
         * A confirmed order enters processing through the
         * existing Phase 7 action.
         *
         * Do not duplicate that lifecycle logic here.
         */
        if (
            $order->status ===
            OrderStatus::Confirmed
        ) {
            $order = $this
                ->startOrderProcessing
                ->execute(
                    order: $order,

                    actor: $actor,

                    note: $note ??
                    'Shipment processing started.',
                );
        }

        /*
         * If the order was already processing, nothing
         * needs to happen to the aggregate lifecycle.
         *
         * Every other state is invalid.
         */
        if (
            $order->status !==
            OrderStatus::Processing ||
            $order->fulfilment_status !==
            FulfilmentStatus::Processing
        ) {
            throw ValidationException::withMessages([
                'status' => 'Only orders in processing fulfilment can process shipments.',
            ]);
        }

        return DB::transaction(
            function () use ($shipment): Shipment {
                /*
                 * Keep lock order consistent:
                 *
                 * Order first
                 * Shipment second
                 */
                $lockedOrder = Order::query()
                    ->whereKey(
                        $shipment->order_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedShipment =
                    Shipment::query()
                        ->whereKey(
                            $shipment->id,
                        )
                        ->where(
                            'order_id',
                            $lockedOrder->id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * Re-check after acquiring locks.
                 */
                if (
                    $lockedOrder->status !==
                    OrderStatus::Processing
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Only processing orders can process shipments.',
                    ]);
                }

                if (
                    $lockedOrder
                        ->fulfilment_status !==
                    FulfilmentStatus::Processing
                ) {
                    throw ValidationException::withMessages([
                        'fulfilment_status' => 'Order fulfilment must be processing before a shipment can be processed.',
                    ]);
                }

                $fromStatus =
                    $lockedShipment
                        ->status;

                if (
                    ! $fromStatus
                        ->canTransitionTo(
                            ShipmentStatus::Processing,
                        )
                ) {
                    throw ValidationException::withMessages([
                        'shipment_status' => sprintf(
                            'Shipment cannot transition from %s to %s.',
                            $fromStatus->label(),
                            ShipmentStatus::Processing
                                ->label(),
                        ),
                    ]);
                }

                $lockedShipment->status =
                    ShipmentStatus::Processing;

                $lockedShipment->save();

                /*
                 * Shipment-state history is intentionally
                 * not added yet because Shipment has no
                 * dedicated history table in the current
                 * foundation.
                 *
                 * Order status history remains owned by
                 * the existing order lifecycle.
                 */
                return $lockedShipment
                    ->refresh()
                    ->load([
                        'order',
                        'shippingMethod',
                        'items',
                        'items.orderItem',
                    ]);
            },
        );
    }
}
