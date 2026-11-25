<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $seo_audit_run_id
 * @property string $url_hash
 * @property string $url
 * @property SeoAuditCrawlState $state
 * @property int $depth
 * @property SeoAuditSubjectType $subject_type
 * @property string|null $subject_key
 * @property int|null $http_status
 * @property string|null $content_type
 * @property string|null $redirect_url
 * @property string|null $canonical_url
 * @property bool|null $is_indexable
 * @property string|null $title
 * @property string|null $meta_description
 * @property int|null $h1_count
 * @property int|null $word_count
 * @property string|null $fetch_error_code
 * @property string|null $fetch_error_message
 * @property Carbon|null $fetched_at
 */
final class SeoAuditCrawlPage extends Model
{
    protected $fillable = [
        'seo_audit_run_id',
        'url_hash',
        'url',
        'state',
        'depth',
        'subject_type',
        'subject_key',
        'http_status',
        'content_type',
        'redirect_url',
        'canonical_url',
        'is_indexable',
        'title',
        'meta_description',
        'h1_count',
        'word_count',
        'fetch_error_code',
        'fetch_error_message',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'seo_audit_run_id' => 'integer',
            'state' => SeoAuditCrawlState::class,
            'depth' => 'integer',
            'subject_type' => SeoAuditSubjectType::class,
            'http_status' => 'integer',
            'is_indexable' => 'boolean',
            'h1_count' => 'integer',
            'word_count' => 'integer',
            'fetched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<SeoAuditRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(
            SeoAuditRun::class,
            'seo_audit_run_id',
        );
    }

    /** @return HasMany<SeoAuditInternalLink, $this> */
    public function outgoingLinks(): HasMany
    {
        return $this
            ->hasMany(
                SeoAuditInternalLink::class,
                'source_page_id',
            )
            ->orderBy('id');
    }

    /** @return HasMany<SeoAuditInternalLink, $this> */
    public function incomingLinks(): HasMany
    {
        return $this
            ->hasMany(
                SeoAuditInternalLink::class,
                'target_page_id',
            )
            ->orderBy('id');
    }
}
