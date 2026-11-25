<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $seo_performance_connection_id
 * @property SeoPerformanceSyncScope $scope
 * @property SeoPerformanceSyncMode $mode
 * @property SeoPerformanceSyncStatus $status
 * @property Carbon $date_from
 * @property Carbon $date_to
 * @property int|null $requested_by_admin_id
 * @property string|null $requested_by_name
 * @property string|null $requested_by_email
 * @property int $attempt_count
 * @property int $rows_received
 * @property int $rows_persisted
 * @property string|null $provider_page_token
 * @property bool $provider_complete
 * @property bool $provider_truncated
 * @property int $queue_pass_count
 * @property string|null $orchestration_version
 * @property Carbon|null $last_progress_at
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property Carbon|null $queued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 */
final class SeoPerformanceSyncRun extends Model
{
    protected $fillable = [
        'public_id',
        'seo_performance_connection_id',
        'scope',
        'mode',
        'status',
        'date_from',
        'date_to',
        'requested_by_admin_id',
        'requested_by_name',
        'requested_by_email',
        'attempt_count',
        'rows_received',
        'rows_persisted',
        'provider_page_token',
        'provider_complete',
        'provider_truncated',
        'queue_pass_count',
        'orchestration_version',
        'last_progress_at',
        'failure_code',
        'failure_message',
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'seo_performance_connection_id' => 'integer',
            'scope' => SeoPerformanceSyncScope::class,
            'mode' => SeoPerformanceSyncMode::class,
            'status' => SeoPerformanceSyncStatus::class,
            'date_from' => 'date',
            'date_to' => 'date',
            'requested_by_admin_id' => 'integer',
            'attempt_count' => 'integer',
            'rows_received' => 'integer',
            'rows_persisted' => 'integer',
            'provider_complete' => 'boolean',
            'provider_truncated' => 'boolean',
            'queue_pass_count' => 'integer',
            'last_progress_at' => 'datetime',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<SeoPerformanceConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(
            SeoPerformanceConnection::class,
            'seo_performance_connection_id',
        );
    }

    /** @return BelongsTo<Admin, $this> */
    public function requestedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by_admin_id');
    }
}
