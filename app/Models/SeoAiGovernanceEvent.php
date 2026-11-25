<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SeoAiGovernanceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'governance_run_id',
        'event_type',
        'actor_admin_id',
        'actor_name',
        'actor_email',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => SeoAiGovernanceEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SeoAiGovernanceRun, $this> */
    public function governanceRun(): BelongsTo
    {
        return $this->belongsTo(SeoAiGovernanceRun::class, 'governance_run_id');
    }
}
