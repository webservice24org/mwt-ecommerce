<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use UnexpectedValueException;

final readonly class SyncShipmentTrackingFromProviderAction
{
    public function __construct(
        private ShippingProviderRegistry $providers,
    ) {}

    /**
     * Fetch the latest remote tracking snapshot and persist
     * safe provider/tracking data.
     *
     * This action deliberately does NOT mutate ShipmentStatus.
     * Provider-status reconciliation belongs to Phase 8.6D.
     *
     * @throws Throwable
     */
    public function execute(
        Shipment $shipment,
    ): ShipmentProviderSubmission {
        $submission = $this->prepare(
            $shipment,
        );

        $provider = $this->providers->resolve(
            $submission->provider_code,
        );

        $externalReference = trim(
            (string) $submission->external_reference,
        );

        if ($externalReference === '') {
            $this->markFailed(
                $submission,
            );

            throw new UnexpectedValueException(
                'The provider submission has no external shipment reference.',
            );
        }

        $providerShipment = Shipment::query()
            ->with([
                'order',
                'shippingMethod',
                'items',
                'items.orderItem',
            ])
            ->findOrFail(
                $shipment->getKey(),
            );

        try {
            $result = $provider->refreshTracking(
                shipment: $providerShipment,

                externalReference: $externalReference,
            );

            return $this->complete(
                submission: $submission,

                result: $result,
            );
        } catch (Throwable $exception) {
            $this->markFailed(
                $submission,
            );

            throw $exception;
        }
    }

    private function prepare(
        Shipment $shipment,
    ): ShipmentProviderSubmission {
        return DB::transaction(
            function () use (
                $shipment,
            ): ShipmentProviderSubmission {
                /*
                 * Keep the fulfilment lock order:
                 *
                 * 1. Order
                 * 2. Shipment
                 * 3. Provider submission
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
                        'provider' => 'Tracking cannot be synchronized until provider submission is complete.',
                    ]);
                }

                if (
                    $submission->provider_code ===
                    ManualShippingProvider::CODE
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'Manual shipments do not have remote tracking to synchronize.',
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

                $submission->tracking_sync_status =
                    ShipmentProviderTrackingSyncStatus::Syncing;

                $submission->tracking_sync_attempt_count =
                    $submission->tracking_sync_attempt_count + 1;

                $submission->last_tracking_sync_attempt_at =
                    now();

                $submission->last_tracking_sync_error =
                    null;

                $submission->save();

                return $submission->refresh();
            },
        );
    }

    private function complete(
        ShipmentProviderSubmission $submission,
        ProviderShipmentTrackingResult $result,
    ): ShipmentProviderSubmission {
        $shipmentSnapshot = Shipment::query()
            ->select([
                'id',
                'order_id',
            ])
            ->findOrFail(
                $submission->shipment_id,
            );

        $tracking =
            $this->validatedTracking(
                $result,
            );

        return DB::transaction(
            function () use (
                $submission,
                $result,
                $shipmentSnapshot,
                $tracking,
            ): ShipmentProviderSubmission {
                Order::query()
                    ->whereKey(
                        $shipmentSnapshot->order_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedShipment = Shipment::query()
                    ->whereKey(
                        $shipmentSnapshot->id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedSubmission =
                    ShipmentProviderSubmission::query()
                        ->whereKey(
                            $submission->getKey(),
                        )
                        ->where(
                            'shipment_id',
                            $lockedShipment->id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedSubmission->status !==
                    ShipmentProviderSubmissionStatus::Submitted
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'Provider submission is no longer in a tracking-syncable state.',
                    ]);
                }

                $lockedSubmission->tracking_sync_status =
                    ShipmentProviderTrackingSyncStatus::Synced;

                $lockedSubmission->provider_status =
                    $tracking['provider_status'];

                $lockedSubmission->last_tracking_synced_at =
                    now();

                $lockedSubmission->last_tracking_sync_error =
                    null;

                $metadata =
                    $lockedSubmission->metadata ?? [];

                $metadata['tracking'] = [
                    'synced_at' => now()->toISOString(),

                    'provider' => $result->metadata,
                ];

                $lockedSubmission->metadata =
                    $metadata;

                $lockedSubmission->save();

                /*
                 * Preserve the 8.5F tracking immutability
                 * contract after a shipment reaches a terminal
                 * state. We may still record provider status and
                 * sync metadata for Delivered/Returned/Cancelled,
                 * but we do not rewrite the shipment's displayed
                 * tracking fields.
                 */
                if (
                    in_array(
                        $lockedShipment->status,
                        [
                            ShipmentStatus::Pending,
                            ShipmentStatus::Processing,
                            ShipmentStatus::Shipped,
                        ],
                        true,
                    )
                ) {
                    $this->applyTracking(
                        shipment: $lockedShipment,

                        tracking: $tracking,
                    );
                }

                /*
                 * Deliberately no ShipmentStatus mutation here.
                 * 8.6D will own provider-status mapping and
                 * reconciliation through existing lifecycle
                 * actions.
                 */

                return $lockedSubmission
                    ->refresh()
                    ->load(
                        'shipment',
                    );
            },
        );
    }

    private function markFailed(
        ShipmentProviderSubmission $submission,
    ): void {
        DB::transaction(
            function () use (
                $submission,
            ): void {
                $lockedSubmission =
                    ShipmentProviderSubmission::query()
                        ->whereKey(
                            $submission->getKey(),
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $lockedSubmission instanceof ShipmentProviderSubmission
                ) {
                    return;
                }

                $lockedSubmission->tracking_sync_status =
                    ShipmentProviderTrackingSyncStatus::Failed;

                /*
                 * Do not persist provider exception text. It may
                 * contain secrets, request URLs or raw payloads.
                 */
                $lockedSubmission->last_tracking_sync_error =
                    'Provider tracking synchronization failed.';

                $lockedSubmission->save();
            },
        );
    }

    /**
     * @return array{
     *     provider_status: string|null,
     *     carrier: string|null,
     *     service: string|null,
     *     tracking_number: string|null,
     *     tracking_url: string|null
     * }
     */
    private function validatedTracking(
        ProviderShipmentTrackingResult $result,
    ): array {
        $trackingUrl =
            $this->providerString(
                value: $result->trackingUrl,

                maximumLength: 2048,

                field: 'tracking URL',
            );

        if (
            $trackingUrl !== null &&
            ! $this->isHttpUrl(
                $trackingUrl,
            )
        ) {
            throw new UnexpectedValueException(
                'The shipping provider returned an invalid tracking URL.',
            );
        }

        return [
            'provider_status' => $this->providerString(
                value: $result->providerStatus,

                maximumLength: 120,

                field: 'provider status',
            ),

            'carrier' => $this->providerString(
                value: $result->carrier,

                maximumLength: 120,

                field: 'carrier',
            ),

            'service' => $this->providerString(
                value: $result->service,

                maximumLength: 120,

                field: 'service',
            ),

            'tracking_number' => $this->providerString(
                value: $result->trackingNumber,

                maximumLength: 190,

                field: 'tracking number',
            ),

            'tracking_url' => $trackingUrl,
        ];
    }

    /**
     * @param  array{
     *     provider_status: string|null,
     *     carrier: string|null,
     *     service: string|null,
     *     tracking_number: string|null,
     *     tracking_url: string|null
     * }  $tracking
     */
    private function applyTracking(
        Shipment $shipment,
        array $tracking,
    ): void {
        if ($tracking['carrier'] !== null) {
            $shipment->carrier =
                $tracking['carrier'];
        }

        if ($tracking['service'] !== null) {
            $shipment->service =
                $tracking['service'];
        }

        if ($tracking['tracking_number'] !== null) {
            $shipment->tracking_number =
                $tracking['tracking_number'];
        }

        if ($tracking['tracking_url'] !== null) {
            $shipment->tracking_url =
                $tracking['tracking_url'];
        }

        $shipment->save();
    }

    private function providerString(
        ?string $value,
        int $maximumLength,
        string $field,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            $value,
        );

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) >
            $maximumLength
        ) {
            throw new UnexpectedValueException(
                sprintf(
                    'The shipping provider returned a %s value that is too long.',
                    $field,
                ),
            );
        }

        return $value;
    }

    private function isHttpUrl(
        string $value,
    ): bool {
        if (
            filter_var(
                $value,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return false;
        }

        $scheme = strtolower(
            (string) parse_url(
                $value,
                PHP_URL_SCHEME,
            ),
        );

        return in_array(
            $scheme,
            [
                'http',
                'https',
            ],
            true,
        );
    }
}
