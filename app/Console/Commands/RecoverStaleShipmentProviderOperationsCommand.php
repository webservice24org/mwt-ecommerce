<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shipping\Actions\RecoverStaleShipmentProviderOperationsAction;
use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Models\ShipmentProviderSubmission;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

final class RecoverStaleShipmentProviderOperationsCommand extends Command
{
    protected $signature =
        'shipping:recover-stale-provider-operations {--minutes=15 : Minimum age before an in-progress provider operation is considered stale}';

    protected $description =
        'Recover stale shipping-provider submission, tracking-sync, and reconciliation states safely.';

    public function handle(
        RecoverStaleShipmentProviderOperationsAction $action,
    ): int {
        $minutes = (int) $this->option('minutes');

        if ($minutes < 5 || $minutes > 1440) {
            $this->error(
                'The --minutes value must be between 5 and 1440.',
            );

            return self::INVALID;
        }

        $cutoff = now()->subMinutes($minutes);

        $counts = [
            'submission' => 0,
            'tracking' => 0,
            'reconciliation' => 0,
        ];

        ShipmentProviderSubmission::query()
            ->where(
                static function (Builder $query) use ($cutoff): void {
                    $query
                        ->where(
                            static function (Builder $query) use ($cutoff): void {
                                $query
                                    ->where(
                                        'status',
                                        ShipmentProviderSubmissionStatus::Submitting->value,
                                    )
                                    ->whereNotNull('last_attempted_at')
                                    ->where('last_attempted_at', '<=', $cutoff);
                            },
                        )
                        ->orWhere(
                            static function (Builder $query) use ($cutoff): void {
                                $query
                                    ->where(
                                        'tracking_sync_status',
                                        ShipmentProviderTrackingSyncStatus::Syncing->value,
                                    )
                                    ->whereNotNull('last_tracking_sync_attempt_at')
                                    ->where('last_tracking_sync_attempt_at', '<=', $cutoff);
                            },
                        )
                        ->orWhere(
                            static function (Builder $query) use ($cutoff): void {
                                $query
                                    ->where(
                                        'reconciliation_status',
                                        ShipmentProviderReconciliationStatus::Reconciling->value,
                                    )
                                    ->whereNotNull('last_reconciliation_attempt_at')
                                    ->where('last_reconciliation_attempt_at', '<=', $cutoff);
                            },
                        );
                },
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($submissions) use ($action, $cutoff, &$counts): void {
                    foreach (
                        $submissions as $submission
                    ) {

                        $result = $action->execute(
                            $submission,
                            $cutoff,
                        );

                        if ($result['submission']) {
                            $counts['submission']++;
                        }

                        if ($result['tracking']) {
                            $counts['tracking']++;
                        }

                        if ($result['reconciliation']) {
                            $counts['reconciliation']++;
                        }
                    }
                },
            );

        $this->info(
            sprintf(
                'Recovered stale provider operations: submissions=%d, tracking=%d, reconciliation=%d.',
                $counts['submission'],
                $counts['tracking'],
                $counts['reconciliation'],
            ),
        );

        return self::SUCCESS;
    }
}
