<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemState;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkPlannedAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_bulk_job_id
 * @property int $position
 * @property AiSeoEntityType $entity_type
 * @property int $entity_id
 * @property string $entity_name
 * @property string $entity_slug
 * @property string $snapshot_hash
 * @property int|null $latest_request_id
 * @property string|null $latest_request_snapshot_hash
 * @property AiSeoBulkItemState $state
 * @property AiSeoBulkPlannedAction $planned_action
 * @property AiSeoBulkItemExecutionStatus $execution_status
 * @property int $attempt_count
 * @property int|null $ai_seo_request_id
 * @property string $reason
 * @property string|null $execution_error_code
 * @property string|null $execution_error_message
 * @property Carbon|null $queued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $last_attempt_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property-read AiSeoBulkJob $job
 */
final class AiSeoBulkJobItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_bulk_job_id',
        'position',
        'entity_type',
        'entity_id',
        'entity_name',
        'entity_slug',
        'snapshot_hash',
        'latest_request_id',
        'latest_request_snapshot_hash',
        'state',
        'planned_action',
        'execution_status',
        'attempt_count',
        'ai_seo_request_id',
        'reason',
        'execution_error_code',
        'execution_error_message',
        'queued_at',
        'started_at',
        'last_attempt_at',
        'completed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_bulk_job_id' => 'integer',

            'position' => 'integer',

            'entity_type' => AiSeoEntityType::class,

            'entity_id' => 'integer',

            'latest_request_id' => 'integer',

            'state' => AiSeoBulkItemState::class,

            'planned_action' => AiSeoBulkPlannedAction::class,

            'execution_status' => AiSeoBulkItemExecutionStatus::class,

            'attempt_count' => 'integer',

            'ai_seo_request_id' => 'integer',

            'queued_at' => 'datetime',

            'started_at' => 'datetime',

            'last_attempt_at' => 'datetime',

            'completed_at' => 'datetime',

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AiSeoBulkJob, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(
            AiSeoBulkJob::class,
            'ai_seo_bulk_job_id',
        );
    }
}
