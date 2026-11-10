<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Jobs\Shipping\SyncShipmentTrackingFromProviderJob;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class QueueShipmentProviderTrackingSyncAction
{
    public function __construct(
        private ShippingProviderRegistry $providers,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        Shipment $shipment,
    ): ShipmentProviderSubmission {
        $submission = DB::transaction(
            function () use (
                $shipment,
            ): ShipmentProviderSubmission {
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

                $submission =
                    ShipmentProviderSubmission::query()
                        ->where(
                            'shipment_id',
                            $lockedShipment->id,
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $submission instanceof ShipmentProviderSubmission
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'This shipment has not been submitted to an external shipping provider.',
                    ]);
                }

                if (
                    $submission->status !==
                    ShipmentProviderSubmissionStatus::Submitted
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'Tracking can only be synchronized after provider submission succeeds.',
                    ]);
                }

                if (
                    $submission->provider_code ===
                    ManualShippingProvider::CODE
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'Manual shipments do not have remote provider tracking.',
                    ]);
                }

                if (
                    ! $this->providers->has(
                        $submission->provider_code,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'The submitted shipment provider is not registered.',
                    ]);
                }

                if (
                    in_array(
                        $submission->tracking_sync_status,
                        [
                            ShipmentProviderTrackingSyncStatus::Queued,
                            ShipmentProviderTrackingSyncStatus::Syncing,
                        ],
                        true,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'Provider tracking synchronization is already queued or running.',
                    ]);
                }

                $submission->tracking_sync_status =
                    ShipmentProviderTrackingSyncStatus::Queued;

                $submission->last_tracking_sync_error =
                    null;

                $submission->save();

                SyncShipmentTrackingFromProviderJob::dispatch(
                    $lockedShipment->id,
                )->afterCommit();

                return $submission->refresh();
            },
        );

        return $submission;
    }
}
