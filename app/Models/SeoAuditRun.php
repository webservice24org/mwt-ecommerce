<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $requested_by_admin_id
 * @property string $requested_by_name
 * @property string $requested_by_email
 * @property SeoAuditRunStatus $status
 * @property string $rules_version
 * @property string|null $orchestration_version
 * @property int $queue_passes
 * @property Carbon|null $catalog_seeded_at
 * @property Carbon|null $crawl_settled_at
 * @property Carbon|null $image_health_enriched_at
 * @property Carbon|null $evaluated_at
 * @property Carbon|null $last_progress_at
 * @property int $urls_scanned
 * @property int $findings_total
 * @property int $critical_count
 * @property int $error_count
 * @property int $warning_count
 * @property int $info_count
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property Carbon $requested_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 */
final class SeoAuditRun extends Model
{
    protected $fillable = [
        'public_id', 'requested_by_admin_id', 'requested_by_name', 'requested_by_email',
        'status', 'rules_version', 'orchestration_version', 'queue_passes',
        'urls_scanned', 'findings_total', 'critical_count', 'error_count',
        'warning_count', 'info_count', 'failure_code', 'failure_message',
        'requested_at', 'started_at', 'completed_at', 'failed_at',
        'catalog_seeded_at', 'crawl_settled_at', 'image_health_enriched_at',
        'evaluated_at', 'last_progress_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_by_admin_id' => 'integer',
            'status' => SeoAuditRunStatus::class,
            'queue_passes' => 'integer',
            'urls_scanned' => 'integer',
            'findings_total' => 'integer',
            'critical_count' => 'integer',
            'error_count' => 'integer',
            'warning_count' => 'integer',
            'info_count' => 'integer',
            'requested_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'catalog_seeded_at' => 'datetime',
            'crawl_settled_at' => 'datetime',
            'image_health_enriched_at' => 'datetime',
            'evaluated_at' => 'datetime',
            'last_progress_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Admin, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by_admin_id');
    }

    /** @return HasMany<SeoAuditFinding, $this> */
    public function findings(): HasMany
    {
        return $this->hasMany(SeoAuditFinding::class)->orderBy('id');
    }

    /** @return HasMany<SeoAuditCrawlPage, $this> */
    public function crawlPages(): HasMany
    {
        return $this
            ->hasMany(
                SeoAuditCrawlPage::class,
                'seo_audit_run_id',
            )
            ->orderBy('id');
    }

    /** @return HasMany<SeoAuditInternalLink, $this> */
    public function internalLinks(): HasMany
    {
        return $this
            ->hasMany(
                SeoAuditInternalLink::class,
                'seo_audit_run_id',
            )
            ->orderBy('id');
    }
}
