<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateShipmentTrackingAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        Shipment $shipment,
        ?string $carrier,
        ?string $service,
        ?string $trackingNumber,
        ?string $trackingUrl,
        ?Model $actor = null,
    ): Shipment {
        return DB::transaction(
            function () use (
                $shipment,
                $carrier,
                $service,
                $trackingNumber,
                $trackingUrl,
                $actor,
            ): Shipment {
                /*
                 * Keep the same lock ordering used by the
                 * physical fulfilment actions:
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

                /*
                 * Manual tracking belongs only to Manual
                 * fulfilment (and legacy shipments that do not
                 * yet have an explicit courier provider).
                 *
                 * Pathao/Steadfast tracking is provider-owned
                 * after 8.8C and must be changed only through
                 * the provider submission/tracking-sync flow.
                 */
                $providerCode =
                    $lockedShipment
                        ->effectiveProviderCode();

                if (
                    $providerCode !== null
                    &&
                    $providerCode !==
                    ManualShippingProvider::CODE
                ) {
                    throw ValidationException::withMessages([
                        'tracking' => 'Tracking for external courier shipments is managed by the provider integration. Use provider tracking sync instead.',
                    ]);
                }

                /*
                 * Tracking details may be prepared before
                 * dispatch and corrected while the parcel
                 * is in transit.
                 *
                 * Delivered, Returned and Cancelled
                 * shipments are treated as immutable.
                 */
                if (
                    ! in_array(
                        $lockedShipment->status,
                        [
                            ShipmentStatus::Pending,
                            ShipmentStatus::Processing,
                            ShipmentStatus::Shipped,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'tracking' => 'Tracking details can no longer be changed for this shipment.',
                    ]);
                }

                $normalizedCarrier =
                    $this->normalize(
                        $carrier,
                    );

                $normalizedService =
                    $this->normalize(
                        $service,
                    );

                $normalizedTrackingNumber =
                    $this->normalize(
                        $trackingNumber,
                    );

                $normalizedTrackingUrl =
                    $this->normalize(
                        $trackingUrl,
                    );

                if (
                    $normalizedCarrier === null
                    &&
                    (
                        $normalizedService !== null
                        ||
                        $normalizedTrackingNumber !== null
                        ||
                        $normalizedTrackingUrl !== null
                    )
                ) {
                    throw ValidationException::withMessages([
                        'carrier' => 'A carrier is required when service or tracking details are provided.',
                    ]);
                }

                if (
                    $lockedShipment->status ===
                    ShipmentStatus::Shipped
                    &&
                    $normalizedCarrier === null
                ) {
                    throw ValidationException::withMessages([
                        'carrier' => 'A shipped manual shipment must keep a carrier.',
                    ]);
                }

                $lockedShipment->carrier =
                    $normalizedCarrier;

                $lockedShipment->service =
                    $normalizedService;

                $lockedShipment->tracking_number =
                    $normalizedTrackingNumber;

                $lockedShipment->tracking_url =
                    $normalizedTrackingUrl;

                /*
                 * There is no dedicated shipment-history
                 * table yet. Preserve only lightweight
                 * audit context in metadata; lifecycle
                 * status remains untouched.
                 */
                $metadata =
                    $lockedShipment->metadata ?? [];

                $metadata['tracking_last_updated'] = [
                    'at' => now()->toISOString(),

                    'actor_type' => $actor
                        ?->getMorphClass(),

                    'actor_id' => $actor
                        ?->getKey(),
                ];

                $lockedShipment->metadata =
                    $metadata;

                $lockedShipment->save();

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

    private function normalize(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            $value,
        );

        return $value === ''
            ? null
            : $value;
    }
}
