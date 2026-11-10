<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ReconcileShipmentProviderStatusAction
{
    public function __construct(
        private ShippingProviderRegistry $providers,
        private StartShipmentProcessingAction $startShipmentProcessing,
        private ShipShipmentAction $shipShipment,
        private DeliverShipmentAction $deliverShipment,
        private ReturnShipmentAction $returnShipment,
    ) {}

    /**
     * Reconcile the latest provider status through existing
     * shipment lifecycle actions. Shipment.status is never
     * assigned directly here.
     *
     * @throws Throwable
     */
    public function execute(
        ShipmentProviderSubmission $submission,
    ): ShipmentProviderSubmission {
        $submission = ShipmentProviderSubmission::query()
            ->findOrFail(
                $submission->getKey(),
            );

        if (
            $submission->status !==
            ShipmentProviderSubmissionStatus::Submitted
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Provider status cannot be reconciled before shipment submission is complete.',
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

        $provider = $this->providers->resolve(
            $submission->provider_code,
        );

        $targetStatus = $provider->mapTrackingStatus(
            $submission->provider_status,
        );

        $submission = $this->beginAttempt(
            submission: $submission,
            targetStatus: $targetStatus,
        );

        if ($targetStatus === null) {
            return $this->finish(
                submission: $submission,
                status: ShipmentProviderReconciliationStatus::Unmapped,
            );
        }

        if ($targetStatus === ShipmentStatus::Cancelled) {
            return $this->finish(
                submission: $submission,
                status: ShipmentProviderReconciliationStatus::Blocked,
                error: 'Provider cancellation requires manual shipment review.',
            );
        }

        $shipment = Shipment::query()
            ->findOrFail(
                $submission->shipment_id,
            );

        $plan = $this->transitionPlan(
            from: $shipment->status,
            to: $targetStatus,
        );

        if ($plan === null || $plan === []) {
            return $this->finish(
                submission: $submission,
                status: ShipmentProviderReconciliationStatus::NoChange,
            );
        }

        try {
            foreach ($plan as $nextStatus) {
                $shipment = $this->transition(
                    shipment: $shipment,
                    status: $nextStatus,
                    note: $this->note(
                        submission: $submission,
                        targetStatus: $targetStatus,
                    ),
                );
            }

            return $this->finish(
                submission: $submission,
                status: ShipmentProviderReconciliationStatus::Applied,
            );
        } catch (ValidationException) {
            return $this->finish(
                submission: $submission,
                status: ShipmentProviderReconciliationStatus::Blocked,
                error: 'Provider status could not be applied because the current order or shipment state blocks the required transition.',
            );
        } catch (Throwable $exception) {
            $this->finish(
                submission: $submission,
                status: ShipmentProviderReconciliationStatus::Failed,
                error: 'Provider status reconciliation failed.',
            );

            throw $exception;
        }
    }

    private function beginAttempt(
        ShipmentProviderSubmission $submission,
        ?ShipmentStatus $targetStatus,
    ): ShipmentProviderSubmission {
        return DB::transaction(
            function () use (
                $submission,
                $targetStatus,
            ): ShipmentProviderSubmission {
                $lockedSubmission =
                    ShipmentProviderSubmission::query()
                        ->whereKey(
                            $submission->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedSubmission->provider_mapped_shipment_status =
                    $targetStatus;

                $lockedSubmission->reconciliation_status =
                    ShipmentProviderReconciliationStatus::Reconciling;

                $lockedSubmission->reconciliation_attempt_count =
                    $lockedSubmission->reconciliation_attempt_count + 1;

                $lockedSubmission->last_reconciliation_attempt_at =
                    now();

                $lockedSubmission->last_reconciliation_error =
                    null;

                $lockedSubmission->save();

                return $lockedSubmission->refresh();
            },
        );
    }

    private function finish(
        ShipmentProviderSubmission $submission,
        ShipmentProviderReconciliationStatus $status,
        ?string $error = null,
    ): ShipmentProviderSubmission {
        return DB::transaction(
            function () use (
                $submission,
                $status,
                $error,
            ): ShipmentProviderSubmission {
                $lockedSubmission =
                    ShipmentProviderSubmission::query()
                        ->whereKey(
                            $submission->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedSubmission->reconciliation_status =
                    $status;

                $lockedSubmission->last_reconciliation_error =
                    $error;

                if (
                    in_array(
                        $status,
                        [
                            ShipmentProviderReconciliationStatus::Applied,
                            ShipmentProviderReconciliationStatus::NoChange,
                            ShipmentProviderReconciliationStatus::Unmapped,
                        ],
                        true,
                    )
                ) {
                    $lockedSubmission->last_reconciled_at =
                        now();
                }

                $lockedSubmission->save();

                return $lockedSubmission
                    ->refresh()
                    ->load(
                        'shipment',
                    );
            },
        );
    }

    /**
     * @return list<ShipmentStatus>|null
     */
    private function transitionPlan(
        ShipmentStatus $from,
        ShipmentStatus $to,
    ): ?array {
        if ($from === $to) {
            return [];
        }

        return match ($from) {
            ShipmentStatus::Pending => match ($to) {
                ShipmentStatus::Processing => [
                    ShipmentStatus::Processing,
                ],

                ShipmentStatus::Shipped => [
                    ShipmentStatus::Processing,
                    ShipmentStatus::Shipped,
                ],

                ShipmentStatus::Delivered => [
                    ShipmentStatus::Processing,
                    ShipmentStatus::Shipped,
                    ShipmentStatus::Delivered,
                ],

                ShipmentStatus::Returned => [
                    ShipmentStatus::Processing,
                    ShipmentStatus::Shipped,
                    ShipmentStatus::Returned,
                ],

                default => null,
            },

            ShipmentStatus::Processing => match ($to) {
                ShipmentStatus::Shipped => [
                    ShipmentStatus::Shipped,
                ],

                ShipmentStatus::Delivered => [
                    ShipmentStatus::Shipped,
                    ShipmentStatus::Delivered,
                ],

                ShipmentStatus::Returned => [
                    ShipmentStatus::Shipped,
                    ShipmentStatus::Returned,
                ],

                default => null,
            },

            ShipmentStatus::Shipped => match ($to) {
                ShipmentStatus::Delivered => [
                    ShipmentStatus::Delivered,
                ],

                ShipmentStatus::Returned => [
                    ShipmentStatus::Returned,
                ],

                default => null,
            },

            ShipmentStatus::Delivered => $to === ShipmentStatus::Returned
                    ? [
                        ShipmentStatus::Returned,
                    ]
                    : null,

            ShipmentStatus::Returned,
            ShipmentStatus::Cancelled => null,
        };
    }

    private function transition(
        Shipment $shipment,
        ShipmentStatus $status,
        string $note,
    ): Shipment {
        return match ($status) {
            ShipmentStatus::Processing => $this->startShipmentProcessing
                ->execute(
                    shipment: $shipment,
                    actor: null,
                    note: $note,
                ),

            ShipmentStatus::Shipped => $this->shipShipment
                ->execute(
                    shipment: $shipment,
                    actor: null,
                    note: $note,
                ),

            ShipmentStatus::Delivered => $this->deliverShipment
                ->execute(
                    shipment: $shipment,
                    actor: null,
                    note: $note,
                ),

            ShipmentStatus::Returned => $this->returnShipment
                ->execute(
                    shipment: $shipment,
                    actor: null,
                    note: $note,
                ),

            ShipmentStatus::Pending,
            ShipmentStatus::Cancelled => throw ValidationException::withMessages([
                'shipment_status' => 'The provider status cannot be applied automatically.',
            ]),
        };
    }

    private function note(
        ShipmentProviderSubmission $submission,
        ShipmentStatus $targetStatus,
    ): string {
        return sprintf(
            'Provider reconciliation: %s status "%s" mapped to %s.',
            $submission->provider_code,
            $submission->provider_status ?? 'unknown',
            $targetStatus->value,
        );
    }
}
