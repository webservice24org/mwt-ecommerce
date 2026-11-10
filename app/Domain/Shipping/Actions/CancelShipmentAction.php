<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CancelShipmentAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        Shipment $shipment,
        ?Model $actor = null,
        ?string $reason = null,
    ): Shipment {
        return DB::transaction(
            function () use ($shipment, $actor, $reason): Shipment {
                /*
                 * Keep lock ordering consistent:
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

                $lockedShipment = Shipment::query()
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
                            ShipmentStatus::Cancelled,
                        )
                ) {
                    throw ValidationException::withMessages([
                        'shipment_status' => sprintf(
                            'Shipment cannot transition from %s to %s.',
                            $fromStatus->label(),
                            ShipmentStatus::Cancelled
                                ->label(),
                        ),
                    ]);
                }

                $metadata =
                    $lockedShipment->metadata ?? [];

                /*
                 * Until we introduce a dedicated shipment
                 * history table, preserve cancellation audit
                 * context inside shipment metadata.
                 */
                $metadata['cancellation'] = [
                    'reason' => $reason,

                    'actor_type' => $actor
                        ?->getMorphClass(),

                    'actor_id' => $actor
                        ?->getKey(),

                    'cancelled_at' => now()->toISOString(),
                ];

                $lockedShipment->status =
                    ShipmentStatus::Cancelled;

                $lockedShipment->metadata =
                    $metadata;

                $lockedShipment->save();

                /*
                 * Important:
                 *
                 * Do NOT:
                 * - cancel the Order
                 * - restore stock
                 * - change aggregate fulfilment
                 *
                 * CreateShipmentAction already ignores
                 * Cancelled shipments when calculating
                 * allocated quantities. Therefore cancelling
                 * the shipment releases its allocation.
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
