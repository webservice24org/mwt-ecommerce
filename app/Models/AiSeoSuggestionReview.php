<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_seo_suggestion_id
 * @property AiSeoSuggestionReviewDecision $decision
 * @property int|null $reviewed_by_admin_id
 * @property string|null $note
 * @property string|null $source_snapshot_hash
 * @property string|null $current_snapshot_hash
 * @property string $freshness_state
 * @property Carbon $reviewed_at
 * @property Carbon $created_at
 */
final class AiSeoSuggestionReview extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_seo_suggestion_id',
        'decision',
        'reviewed_by_admin_id',
        'note',
        'source_snapshot_hash',
        'current_snapshot_hash',
        'freshness_state',
        'reviewed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_seo_suggestion_id' => 'integer',

            'decision' => AiSeoSuggestionReviewDecision::class,

            'reviewed_by_admin_id' => 'integer',

            'reviewed_at' => 'datetime',

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AiSeoSuggestion, $this>
     */
    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(
            AiSeoSuggestion::class,
            'ai_seo_suggestion_id',
        );
    }
}
