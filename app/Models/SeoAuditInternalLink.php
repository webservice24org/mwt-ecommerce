<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $seo_audit_run_id
 * @property int $source_page_id
 * @property int $target_page_id
 * @property string $edge_hash
 * @property string $target_url_hash
 * @property string $target_url
 * @property string|null $anchor_text
 * @property bool $nofollow
 * @property int $occurrences
 */
final class SeoAuditInternalLink extends Model
{
    protected $fillable = [
        'seo_audit_run_id',
        'source_page_id',
        'target_page_id',
        'edge_hash',
        'target_url_hash',
        'target_url',
        'anchor_text',
        'nofollow',
        'occurrences',
    ];

    protected function casts(): array
    {
        return [
            'seo_audit_run_id' => 'integer',
            'source_page_id' => 'integer',
            'target_page_id' => 'integer',
            'nofollow' => 'boolean',
            'occurrences' => 'integer',
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

    /** @return BelongsTo<SeoAuditCrawlPage, $this> */
    public function sourcePage(): BelongsTo
    {
        return $this->belongsTo(
            SeoAuditCrawlPage::class,
            'source_page_id',
        );
    }

    /** @return BelongsTo<SeoAuditCrawlPage, $this> */
    public function targetPage(): BelongsTo
    {
        return $this->belongsTo(
            SeoAuditCrawlPage::class,
            'target_page_id',
        );
    }
}
