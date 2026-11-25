<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisEnrichmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_analysis_id
 * @property string $analysis_input_hash
 * @property string $prompt_version
 * @property AiSeoAnalysisEnrichmentStatus $status
 * @property string $provider
 * @property string $model_name
 * @property string $input_hash
 * @property array<string, mixed> $input_context
 * @property string|null $summary
 * @property list<array<string, mixed>>|null $actions
 * @property string|null $provider_response_id
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int|null $enriched_by_admin_id
 * @property Carbon $started_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 */
final class AiSeoAnalysisEnrichment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_analysis_id',
        'analysis_input_hash',
        'prompt_version',
        'status',
        'provider',
        'model_name',
        'input_hash',
        'input_context',
        'summary',
        'actions',
        'provider_response_id',
        'error_code',
        'error_message',
        'enriched_by_admin_id',
        'started_at',
        'completed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_analysis_id' => 'integer',

            'status' => AiSeoAnalysisEnrichmentStatus::class,

            'input_context' => 'array',

            'actions' => 'array',

            'enriched_by_admin_id' => 'integer',

            'started_at' => 'datetime',

            'completed_at' => 'datetime',

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AiSeoAnalysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(
            AiSeoAnalysis::class,
            'ai_seo_analysis_id',
        );
    }
}
