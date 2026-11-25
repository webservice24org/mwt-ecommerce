<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property AiSeoBulkJobStatus $status
 * @property string $plan_hash
 * @property list<string> $entity_types
 * @property array<string, mixed> $filters
 * @property array<string, int> $summary
 * @property array<string, int>|null $execution_summary
 * @property int|null $planned_by_admin_id
 * @property Carbon $planned_at
 * @property Carbon|null $started_at
 * @property Carbon|null $last_dispatched_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 */
final class AiSeoBulkJob extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reference',
        'status',
        'plan_hash',
        'entity_types',
        'filters',
        'summary',
        'execution_summary',
        'planned_by_admin_id',
        'planned_at',
        'started_at',
        'last_dispatched_at',
        'completed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiSeoBulkJobStatus::class,

            'entity_types' => 'array',

            'filters' => 'array',

            'summary' => 'array',

            'execution_summary' => 'array',

            'planned_by_admin_id' => 'integer',

            'planned_at' => 'datetime',

            'started_at' => 'datetime',

            'last_dispatched_at' => 'datetime',

            'completed_at' => 'datetime',

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AiSeoBulkJobItem, $this>
     */
    public function items(): HasMany
    {
        return $this
            ->hasMany(
                AiSeoBulkJobItem::class,
                'ai_seo_bulk_job_id',
            )
            ->orderBy(
                'position',
            );
    }
}
