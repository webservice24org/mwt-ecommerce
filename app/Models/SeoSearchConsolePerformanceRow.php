<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Performance\Enums\SearchConsolePerformanceDimension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $seo_performance_sync_run_id
 * @property int $seo_performance_connection_id
 * @property SearchConsolePerformanceDimension $dimension_type
 * @property string $search_type
 * @property Carbon $data_date
 * @property string $dimension_value
 * @property string $dimension_hash
 * @property string $clicks
 * @property string $impressions
 * @property string $ctr
 * @property string $position
 */
final class SeoSearchConsolePerformanceRow extends Model
{
    protected $fillable = [
        'seo_performance_sync_run_id',
        'seo_performance_connection_id',
        'dimension_type',
        'search_type',
        'data_date',
        'dimension_value',
        'dimension_hash',
        'clicks',
        'impressions',
        'ctr',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'seo_performance_sync_run_id' => 'integer',
            'seo_performance_connection_id' => 'integer',
            'dimension_type' => SearchConsolePerformanceDimension::class,
            'data_date' => 'date',
            'clicks' => 'decimal:4',
            'impressions' => 'decimal:4',
            'ctr' => 'decimal:10',
            'position' => 'decimal:4',
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
