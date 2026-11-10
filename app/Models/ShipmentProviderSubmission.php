<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shipment_id
 * @property string $provider_code
 * @property string $idempotency_key
 * @property ShipmentProviderSubmissionStatus $status
 * @property string|null $external_reference
 * @property int $attempt_count
 * @property Carbon|null $last_attempted_at
 * @property Carbon|null $submitted_at
 * @property string|null $last_error
 * @property ShipmentProviderTrackingSyncStatus $tracking_sync_status
 * @property int $tracking_sync_attempt_count
 * @property string|null $provider_status
 * @property ShipmentStatus|null $provider_mapped_shipment_status
 * @property ShipmentProviderReconciliationStatus $reconciliation_status
 * @property int $reconciliation_attempt_count
 * @property Carbon|null $last_reconciliation_attempt_at
 * @property Carbon|null $last_reconciled_at
 * @property string|null $last_reconciliation_error
 * @property Carbon|null $last_tracking_sync_attempt_at
 * @property Carbon|null $last_tracking_synced_at
 * @property string|null $last_tracking_sync_error
 * @property array<string, mixed>|null $metadata
 */
final class ShipmentProviderSubmission extends Model
{
    protected $fillable = [
        'shipment_id',
        'provider_code',
        'idempotency_key',
        'status',
        'external_reference',
        'attempt_count',
        'last_attempted_at',
        'submitted_at',
        'last_error',
        'tracking_sync_status',
        'tracking_sync_attempt_count',
        'provider_status',
        'provider_mapped_shipment_status',
        'reconciliation_status',
        'reconciliation_attempt_count',
        'last_reconciliation_attempt_at',
        'last_reconciled_at',
        'last_reconciliation_error',
        'last_tracking_sync_attempt_at',
        'last_tracking_synced_at',
        'last_tracking_sync_error',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentProviderSubmissionStatus::class,
            'attempt_count' => 'integer',
            'last_attempted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::class,
            'tracking_sync_attempt_count' => 'integer',
            'provider_mapped_shipment_status' => ShipmentStatus::class,
            'reconciliation_status' => ShipmentProviderReconciliationStatus::class,
            'reconciliation_attempt_count' => 'integer',
            'last_reconciliation_attempt_at' => 'datetime',
            'last_reconciled_at' => 'datetime',
            'last_tracking_sync_attempt_at' => 'datetime',
            'last_tracking_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(
            Shipment::class,
        );
    }
}
