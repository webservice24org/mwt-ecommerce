<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SeoAiGovernanceAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'governance_run_id',
        'attempt_number',
        'outcome',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'cost_amount_minor',
        'cost_currency',
        'failure_code',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'total_tokens' => 'integer',
            'cost_amount_minor' => 'integer',
            'recorded_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SeoAiGovernanceRun, $this> */
    public function governanceRun(): BelongsTo
    {
        return $this->belongsTo(SeoAiGovernanceRun::class, 'governance_run_id');
    }
}
