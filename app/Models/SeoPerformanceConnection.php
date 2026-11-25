<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property SeoPerformanceProvider $provider
 * @property SeoPerformanceConnectionStatus $status
 * @property string $display_name
 * @property string $property_identifier
 * @property string|null $property_url
 * @property string $timezone
 * @property list<string> $capabilities
 * @property int|null $created_by_admin_id
 * @property string|null $created_by_name
 * @property string|null $created_by_email
 * @property Carbon|null $verified_at
 * @property string|null $last_error_code
 * @property string|null $last_error_message
 */
final class SeoPerformanceConnection extends Model
{
    protected $fillable = [
        'public_id',
        'provider',
        'status',
        'display_name',
        'property_identifier',
        'property_url',
        'timezone',
        'capabilities',
        'created_by_admin_id',
        'created_by_name',
        'created_by_email',
        'verified_at',
        'last_error_code',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'provider' => SeoPerformanceProvider::class,
            'status' => SeoPerformanceConnectionStatus::class,
            'capabilities' => 'array',
            'created_by_admin_id' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Admin, $this> */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /** @return HasMany<SeoPerformanceSyncRun, $this> */
    public function syncRuns(): HasMany
    {
        return $this->hasMany(
            SeoPerformanceSyncRun::class,
            'seo_performance_connection_id',
        );
    }
}
