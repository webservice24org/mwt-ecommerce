<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $seo_performance_sync_run_id
 * @property int $seo_performance_connection_id
 * @property Carbon $data_date
 * @property string $landing_page
 * @property string $landing_page_hash
 * @property string $sessions
 * @property string $engaged_sessions
 * @property string $active_users
 * @property string $new_users
 * @property string $key_events
 * @property string $total_revenue
 */
final class SeoGoogleAnalyticsLandingPageRow extends Model
{
    protected $fillable = [
        'seo_performance_sync_run_id',
        'seo_performance_connection_id',
        'data_date',
        'landing_page',
        'landing_page_hash',
        'sessions',
        'engaged_sessions',
        'active_users',
        'new_users',
        'key_events',
        'total_revenue',
    ];

    protected function casts(): array
    {
        return [
            'seo_performance_sync_run_id' => 'integer',
            'seo_performance_connection_id' => 'integer',
            'data_date' => 'date',
            'sessions' => 'decimal:4',
            'engaged_sessions' => 'decimal:4',
            'active_users' => 'decimal:4',
            'new_users' => 'decimal:4',
            'key_events' => 'decimal:4',
            'total_revenue' => 'decimal:6',
        ];
    }

    /** @return BelongsTo<SeoPerformanceSyncRun, $this> */
    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(
            SeoPerformanceSyncRun::class,
            'seo_performance_sync_run_id',
        );
    }

    /** @return BelongsTo<SeoPerformanceConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(
            SeoPerformanceConnection::class,
            'seo_performance_connection_id',
        );
    }
}
