<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $seo_audit_run_id
 * @property int $source_page_id
 * @property int $target_page_id
 * @property string $fingerprint
 * @property string $engine_version
 * @property SeoAuditInternalLinkReviewState $review_state
 * @property int $score
 * @property SeoAuditSubjectType $target_subject_type
 * @property string|null $target_subject_key
 * @property string $source_url
 * @property string $target_url
 * @property string|null $suggested_anchor
 * @property string $reason
 */
final class SeoAuditInternalLinkOpportunity extends Model
{
    protected $fillable = [
        'seo_audit_run_id',
        'source_page_id',
        'target_page_id',
        'fingerprint',
        'engine_version',
        'review_state',
        'score',
        'target_subject_type',
        'target_subject_key',
        'source_url',
        'target_url',
        'suggested_anchor',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'seo_audit_run_id' => 'integer',
            'source_page_id' => 'integer',
            'target_page_id' => 'integer',
            'review_state' => SeoAuditInternalLinkReviewState::class,
            'score' => 'integer',
            'target_subject_type' => SeoAuditSubjectType::class,
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
