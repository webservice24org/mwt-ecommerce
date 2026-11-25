<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $seo_audit_run_id
 * @property int $source_page_id
 * @property string $observation_hash
 * @property string $image_url_hash
 * @property string $image_url
 * @property bool $alt_attribute_present
 * @property string|null $alt_text
 * @property int $occurrences
 */
final class SeoAuditPageImage extends Model
{
    protected $fillable = [
        'seo_audit_run_id',
        'source_page_id',
        'observation_hash',
        'image_url_hash',
        'image_url',
        'alt_attribute_present',
        'alt_text',
        'occurrences',
    ];

    protected function casts(): array
    {
        return [
            'seo_audit_run_id' => 'integer',
            'source_page_id' => 'integer',
            'alt_attribute_present' => 'boolean',
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
}
