<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkApplyItemResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_bulk_apply_batch_id
 * @property int $ai_seo_bulk_job_item_id
 * @property int $ai_seo_suggestion_id
 * @property int|null $ai_seo_suggestion_review_id
 * @property int $ai_seo_request_id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $field
 * @property AiSeoBulkApplyItemResult $result
 * @property string|null $baseline_value
 * @property string|null $before_value
 * @property string $proposed_value
 * @property string|null $after_value
 * @property string|null $source_snapshot_hash
 * @property string|null $current_snapshot_hash
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon $created_at
 */
final class AiSeoBulkApplyBatchItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_bulk_apply_batch_id',
        'ai_seo_bulk_job_item_id',
        'ai_seo_suggestion_id',
        'ai_seo_suggestion_review_id',
        'ai_seo_request_id',
        'entity_type',
        'entity_id',
        'field',
        'result',
        'baseline_value',
        'before_value',
        'proposed_value',
        'after_value',
        'source_snapshot_hash',
        'current_snapshot_hash',
        'error_code',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_bulk_apply_batch_id' => 'integer',

            'ai_seo_bulk_job_item_id' => 'integer',

            'ai_seo_suggestion_id' => 'integer',

            'ai_seo_suggestion_review_id' => 'integer',

            'ai_seo_request_id' => 'integer',

            'entity_id' => 'integer',

            'result' => AiSeoBulkApplyItemResult::class,

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AiSeoBulkApplyBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            AiSeoBulkApplyBatch::class,
            'ai_seo_bulk_apply_batch_id',
        );
    }
}
