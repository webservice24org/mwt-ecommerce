<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\AiGovernance\Enums\SeoAiApprovalStatus;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SeoAiGovernanceRun extends Model
{
    protected $fillable = [
        'public_id',
        'purpose',
        'subject_type',
        'subject_key',
        'provider',
        'model_name',
        'prompt_version',
        'input_snapshot_hash',
        'input_field_names',
        'status',
        'approval_status',
        'generated_output',
        'generated_at',
        'requested_by_admin_id',
        'requested_by_name',
        'requested_by_email',
        'reviewed_by_admin_id',
        'reviewed_by_name',
        'reviewed_by_email',
        'reviewed_at',
        'published_at',
        'attempt_count',
        'failure_code',
        'failure_message',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'cost_amount_minor',
        'cost_currency',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => SeoAiGovernancePurpose::class,
            'status' => SeoAiGovernanceStatus::class,
            'approval_status' => SeoAiApprovalStatus::class,
            'input_field_names' => 'array',
            'generated_output' => 'array',
            'generated_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'attempt_count' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'total_tokens' => 'integer',
            'cost_amount_minor' => 'integer',
        ];
    }

    /** @return HasMany<SeoAiGovernanceEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(SeoAiGovernanceEvent::class, 'governance_run_id')->orderBy('id');
    }
}
