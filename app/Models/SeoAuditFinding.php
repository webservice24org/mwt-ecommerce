<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $seo_audit_run_id
 * @property string $fingerprint
 * @property SeoAuditRule $rule_key
 * @property SeoAuditSeverity $severity
 * @property SeoAuditSubjectType $subject_type
 * @property string|null $subject_key
 * @property string|null $url
 * @property string|null $source_url
 * @property string $message
 * @property array<string, mixed> $evidence
 * @property string|null $remediation
 */
final class SeoAuditFinding extends Model
{
    protected $fillable = [
        'seo_audit_run_id', 'fingerprint', 'rule_key', 'severity', 'subject_type',
        'subject_key', 'url', 'source_url', 'message', 'evidence', 'remediation',
    ];

    protected function casts(): array
    {
        return [
            'seo_audit_run_id' => 'integer',
            'rule_key' => SeoAuditRule::class,
            'severity' => SeoAuditSeverity::class,
            'subject_type' => SeoAuditSubjectType::class,
            'evidence' => 'array',
        ];
    }

    /** @return BelongsTo<SeoAuditRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(SeoAuditRun::class, 'seo_audit_run_id');
    }
}
