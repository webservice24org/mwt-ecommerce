<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkReviewBatchStatus;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_bulk_job_id
 * @property AiSeoSuggestionReviewDecision $decision
 * @property AiSeoBulkReviewBatchStatus $status
 * @property array<string, int> $summary
 * @property int|null $reviewed_by_admin_id
 * @property string|null $note
 * @property Carbon $reviewed_at
 * @property Carbon $created_at
 */
final class AiSeoBulkReviewBatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_bulk_job_id',
        'decision',
        'status',
        'summary',
        'reviewed_by_admin_id',
        'note',
        'reviewed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_bulk_job_id' => 'integer',
            'decision' => AiSeoSuggestionReviewDecision::class,
            'status' => AiSeoBulkReviewBatchStatus::class,
            'summary' => 'array',
            'reviewed_by_admin_id' => 'integer',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AiSeoBulkJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(AiSeoBulkJob::class, 'ai_seo_bulk_job_id');
    }

    /** @return HasMany<AiSeoBulkReviewBatchItem, $this> */
    public function items(): HasMany
    {
        return $this
            ->hasMany(AiSeoBulkReviewBatchItem::class, 'ai_seo_bulk_review_batch_id')
            ->orderBy('id');
    }
}
