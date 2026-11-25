<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkApplyBatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_bulk_job_id
 * @property AiSeoBulkApplyBatchStatus $status
 * @property array<string, int> $summary
 * @property int|null $applied_by_admin_id
 * @property string|null $note
 * @property Carbon $applied_at
 * @property Carbon $created_at
 */
final class AiSeoBulkApplyBatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_bulk_job_id',
        'status',
        'summary',
        'applied_by_admin_id',
        'note',
        'applied_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_bulk_job_id' => 'integer',

            'status' => AiSeoBulkApplyBatchStatus::class,

            'summary' => 'array',

            'applied_by_admin_id' => 'integer',

            'applied_at' => 'datetime',

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

    /**
     * @return HasMany<AiSeoBulkApplyBatchItem, $this>
     */
    public function items(): HasMany
    {
        return $this
            ->hasMany(
                AiSeoBulkApplyBatchItem::class,
                'ai_seo_bulk_apply_batch_id',
            )
            ->orderBy('id');
    }
}
