<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Shipping\Actions\RecoverStaleShipmentProviderOperationsAction;
use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecoverStaleShipmentProviderOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_submitting_state_becomes_review_required_never_failed_retryable(): void
    {
        $submission = $this->submission([
            'status' => ShipmentProviderSubmissionStatus::Submitting,
            'last_attempted_at' => now()->subMinutes(30),
        ]);

        $result = app(
            RecoverStaleShipmentProviderOperationsAction::class,
        )->execute(
            $submission,
            now()->subMinutes(15),
        );

        $this->assertTrue($result['submission']);

        $submission->refresh();

        $this->assertSame(
            ShipmentProviderSubmissionStatus::ReviewRequired,
            $submission->status,
        );
    }

    public function test_stale_tracking_and_reconciliation_are_failed_for_safe_later_retry(): void
    {
        $submission = $this->submission([
            'status' => ShipmentProviderSubmissionStatus::Submitted,
            'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Syncing,
            'last_tracking_sync_attempt_at' => now()->subMinutes(30),
            'reconciliation_status' => ShipmentProviderReconciliationStatus::Reconciling,
            'last_reconciliation_attempt_at' => now()->subMinutes(30),
        ]);

        $result = app(
            RecoverStaleShipmentProviderOperationsAction::class,
        )->execute(
            $submission,
            now()->subMinutes(15),
        );

        $this->assertTrue($result['tracking']);
        $this->assertTrue($result['reconciliation']);

        $submission->refresh();

        $this->assertSame(
            ShipmentProviderTrackingSyncStatus::Failed,
            $submission->tracking_sync_status,
        );

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Failed,
            $submission->reconciliation_status,
        );
    }

    public function test_recent_in_progress_state_is_not_recovered(): void
    {
        $submission = $this->submission([
            'status' => ShipmentProviderSubmissionStatus::Submitting,
            'last_attempted_at' => now()->subMinutes(2),
        ]);

        $result = app(
            RecoverStaleShipmentProviderOperationsAction::class,
        )->execute(
            $submission,
            now()->subMinutes(15),
        );

        $this->assertFalse($result['submission']);

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Submitting,
            $submission->refresh()->status,
        );
    }

    public function test_command_recovers_stale_rows_and_rejects_unsafe_threshold(): void
    {
        $submission = $this->submission([
            'status' => ShipmentProviderSubmissionStatus::Submitting,
            'last_attempted_at' => now()->subMinutes(30),
        ]);

        $this
            ->artisan(
                'shipping:recover-stale-provider-operations',
                ['--minutes' => 15],
            )
            ->assertSuccessful();

        $this->assertSame(
            ShipmentProviderSubmissionStatus::ReviewRequired,
            $submission->refresh()->status,
        );

        $this
            ->artisan(
                'shipping:recover-stale-provider-operations',
                ['--minutes' => 1],
            )
            ->assertExitCode(2);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submission(array $overrides): ShipmentProviderSubmission
    {
        $shipment = Shipment::factory()->create();

        return ShipmentProviderSubmission::query()->create(
            array_merge(
                [
                    'shipment_id' => $shipment->id,
                    'provider_code' => 'pathao',
                    'idempotency_key' => '00000000-0000-4000-8000-000000000007',
                    'status' => ShipmentProviderSubmissionStatus::Failed,
                    'external_reference' => null,
                    'attempt_count' => 1,
                    'last_attempted_at' => now(),
                    'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Pending,
                    'tracking_sync_attempt_count' => 0,
                    'reconciliation_status' => ShipmentProviderReconciliationStatus::Pending,
                    'reconciliation_attempt_count' => 0,
                ],
                $overrides,
            ),
        );
    }
}
