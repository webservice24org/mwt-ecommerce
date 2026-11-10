<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Models\ShipmentProviderSubmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class RecoverStaleShipmentProviderOperationsAction
{
    /**
     * @return array{
     *     submission: bool,
     *     tracking: bool,
     *     reconciliation: bool
     * }
     */
    public function execute(
        ShipmentProviderSubmission $submission,
        Carbon $cutoff,
    ): array {
        return DB::transaction(
            function () use ($submission, $cutoff): array {
                $locked = ShipmentProviderSubmission::query()
                    ->whereKey($submission->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $recoveredSubmission = false;
                $recoveredTracking = false;
                $recoveredReconciliation = false;

                if (
                    $locked->status === ShipmentProviderSubmissionStatus::Submitting &&
                    $locked->last_attempted_at !== null &&
                    $locked->last_attempted_at->lte($cutoff)
                ) {
                    /*
                     * A process can die after sending a create
                     * request but before recording the response.
                     * Never assume the remote parcel does not
                     * exist. Force manual provider review.
                     */
                    $locked->status =
                        ShipmentProviderSubmissionStatus::ReviewRequired;

                    $locked->last_error =
                        'Provider submission became stale with an uncertain remote outcome. Manual provider review is required before retry.';

                    $recoveredSubmission = true;
                }

                if (
                    $locked->tracking_sync_status === ShipmentProviderTrackingSyncStatus::Syncing &&
                    $locked->last_tracking_sync_attempt_at !== null &&
                    $locked->last_tracking_sync_attempt_at->lte($cutoff)
                ) {
                    /* Tracking reads are safe to retry. */
                    $locked->tracking_sync_status =
                        ShipmentProviderTrackingSyncStatus::Failed;

                    $locked->last_tracking_sync_error =
                        'Provider tracking synchronization became stale and can be queued again.';

                    $recoveredTracking = true;
                }

                if (
                    $locked->reconciliation_status === ShipmentProviderReconciliationStatus::Reconciling &&
                    $locked->last_reconciliation_attempt_at !== null &&
                    $locked->last_reconciliation_attempt_at->lte($cutoff)
                ) {
                    /*
                     * Reconciliation is local and idempotent
                     * through the existing shipment actions.
                     * Mark stale work failed; the next verified
                     * provider sync may reconcile again.
                     */
                    $locked->reconciliation_status =
                        ShipmentProviderReconciliationStatus::Failed;

                    $locked->last_reconciliation_error =
                        'Provider status reconciliation became stale and can be retried after the next provider sync.';

                    $recoveredReconciliation = true;
                }

                if (
                    $recoveredSubmission ||
                    $recoveredTracking ||
                    $recoveredReconciliation
                ) {
                    $locked->save();
                }

                return [
                    'submission' => $recoveredSubmission,
                    'tracking' => $recoveredTracking,
                    'reconciliation' => $recoveredReconciliation,
                ];
            },
        );
    }
}
