<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderReviewResolution;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ResolveShipmentProviderReviewAction
{
    public function execute(
        Shipment $shipment,
        ShipmentProviderReviewResolution $resolution,
        ?string $externalReference,
        string $note,
        Model $actor,
    ): ShipmentProviderSubmission {
        return DB::transaction(
            function () use (
                $shipment,
                $resolution,
                $externalReference,
                $note,
                $actor,
            ): ShipmentProviderSubmission {
                $lockedOrder = Order::query()
                    ->whereKey($shipment->order_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedShipment = Shipment::query()
                    ->whereKey($shipment->getKey())
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $submission = ShipmentProviderSubmission::query()
                    ->where('shipment_id', $lockedShipment->id)
                    ->lockForUpdate()
                    ->first();

                if (! $submission instanceof ShipmentProviderSubmission) {
                    throw ValidationException::withMessages([
                        'provider' => 'This shipment has no provider submission to review.',
                    ]);
                }

                if (
                    $submission->status !==
                    ShipmentProviderSubmissionStatus::ReviewRequired
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'This provider submission does not require manual review.',
                    ]);
                }

                $normalizedNote = trim($note);

                if (mb_strlen($normalizedNote) < 3 || mb_strlen($normalizedNote) > 500) {
                    throw ValidationException::withMessages([
                        'note' => 'The review note must be between 3 and 500 characters.',
                    ]);
                }

                if ($resolution === ShipmentProviderReviewResolution::ConfirmExisting) {
                    $reference = $this->externalReference($externalReference);

                    $duplicateExists = ShipmentProviderSubmission::query()
                        ->where('provider_code', $submission->provider_code)
                        ->where('external_reference', $reference)
                        ->where('id', '!=', $submission->id)
                        ->exists();

                    if ($duplicateExists) {
                        throw ValidationException::withMessages([
                            'external_reference' => 'This provider reference is already linked to another shipment.',
                        ]);
                    }

                    $submission->status =
                        ShipmentProviderSubmissionStatus::Submitted;

                    $submission->external_reference = $reference;
                    $submission->submitted_at ??= now();
                    $submission->last_error = null;

                    $submission->tracking_sync_status =
                        ShipmentProviderTrackingSyncStatus::Pending;

                    $submission->last_tracking_sync_error = null;

                    $submission->reconciliation_status =
                        ShipmentProviderReconciliationStatus::Pending;

                    $submission->last_reconciliation_error = null;
                } else {
                    /*
                     * The administrator has checked the provider
                     * and explicitly confirmed that no remote
                     * shipment exists. Keep the original stable
                     * idempotency key; a later retry will reuse it.
                     */
                    $submission->status =
                        ShipmentProviderSubmissionStatus::Failed;

                    $submission->external_reference = null;
                    $submission->submitted_at = null;
                    $submission->last_error =
                        'Manual review confirmed that provider submission retry is permitted.';
                }

                $metadata = $submission->metadata ?? [];

                $history = $metadata['recovery_history'] ?? [];

                if (! is_array($history)) {
                    $history = [];
                }

                $history[] = [
                    'resolution' => $resolution->value,
                    'actor_type' => $actor->getMorphClass(),
                    'actor_id' => $actor->getKey(),
                    'note' => $normalizedNote,
                    'resolved_at' => now()->toISOString(),
                ];

                /* Keep operational metadata bounded. */
                $metadata['recovery_history'] = array_values(
                    array_slice($history, -20),
                );

                $submission->metadata = $metadata;
                $submission->save();

                return $submission
                    ->refresh()
                    ->load('shipment');
            },
        );
    }

    private function externalReference(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '' || mb_strlen($value) > 190) {
            throw ValidationException::withMessages([
                'external_reference' => 'Enter the provider reference shown in the provider panel.',
            ]);
        }

        return $value;
    }
}
