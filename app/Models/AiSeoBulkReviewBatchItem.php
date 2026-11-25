<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkReviewItemResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_bulk_review_batch_id
 * @property int $ai_seo_bulk_job_item_id
 * @property int $ai_seo_suggestion_id
 * @property int $ai_seo_request_id
 * @property string $field
 * @property AiSeoBulkReviewItemResult $result
 * @property string|null $previous_decision
 * @property string|null $resulting_decision
 * @property string $freshness_state
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon $created_at
 */
final class AiSeoBulkReviewBatchItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_bulk_review_batch_id',
        'ai_seo_bulk_job_item_id',
        'ai_seo_suggestion_id',
        'ai_seo_request_id',
        'field',
        'result',
        'previous_decision',
        'resulting_decision',
        'freshness_state',
        'error_code',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_bulk_review_batch_id' => 'integer',
            'ai_seo_bulk_job_item_id' => 'integer',
            'ai_seo_suggestion_id' => 'integer',
            'ai_seo_request_id' => 'integer',
            'result' => AiSeoBulkReviewItemResult::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AiSeoBulkReviewBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(AiSeoBulkReviewBatch::class, 'ai_seo_bulk_review_batch_id');
    }
}
