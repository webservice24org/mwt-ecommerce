<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_suggestion_id
 * @property int $ai_seo_suggestion_review_id
 * @property int $ai_seo_request_id
 * @property int $ai_seo_bulk_job_id
 * @property int $ai_seo_bulk_job_item_id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $field
 * @property string|null $baseline_value
 * @property string|null $before_value
 * @property string $applied_value
 * @property string $source_snapshot_hash
 * @property string $current_snapshot_hash
 * @property int|null $applied_by_admin_id
 * @property Carbon $applied_at
 * @property Carbon $created_at
 */
final class AiSeoSuggestionApplication extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_suggestion_id',
        'ai_seo_suggestion_review_id',
        'ai_seo_request_id',
        'ai_seo_bulk_job_id',
        'ai_seo_bulk_job_item_id',
        'entity_type',
        'entity_id',
        'field',
        'baseline_value',
        'before_value',
        'applied_value',
        'source_snapshot_hash',
        'current_snapshot_hash',
        'applied_by_admin_id',
        'applied_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_suggestion_id' => 'integer',

            'ai_seo_suggestion_review_id' => 'integer',

            'ai_seo_request_id' => 'integer',

            'ai_seo_bulk_job_id' => 'integer',

            'ai_seo_bulk_job_item_id' => 'integer',

            'entity_id' => 'integer',

            'applied_by_admin_id' => 'integer',

            'applied_at' => 'datetime',

            'created_at' => 'datetime',
        ];
    }
}
