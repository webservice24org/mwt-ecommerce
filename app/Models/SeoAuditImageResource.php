<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Audit\Enums\SeoAuditImageHealthState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $seo_audit_run_id
 * @property string $image_url_hash
 * @property string $image_url
 * @property SeoAuditImageHealthState $state
 * @property int|null $http_status
 * @property string|null $content_type
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property Carbon|null $checked_at
 */
final class SeoAuditImageResource extends Model
{
    protected $fillable = [
        'seo_audit_run_id',
        'image_url_hash',
        'image_url',
        'state',
        'http_status',
        'content_type',
        'failure_code',
        'failure_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'seo_audit_run_id' => 'integer',
            'state' => SeoAuditImageHealthState::class,
            'http_status' => 'integer',
            'checked_at' => 'datetime',
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
}
