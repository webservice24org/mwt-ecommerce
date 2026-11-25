<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $ai_seo_request_id
 * @property AiSeoSuggestionField $field
 * @property string $value
 * @property string|null $rationale
 * @property int $position
 * @property-read AiSeoRequest $request
 * @property-read AiSeoSuggestionReview|null $latestReview
 */
final class AiSeoSuggestion extends Model
{
    protected $fillable = [
        'ai_seo_request_id',
        'field',
        'value',
        'rationale',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'field' => AiSeoSuggestionField::class,

            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AiSeoRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(
            AiSeoRequest::class,
            'ai_seo_request_id',
        );
    }

    /**
     * @return HasMany<AiSeoSuggestionReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(
            AiSeoSuggestionReview::class,
            'ai_seo_suggestion_id',
        );
    }

    /**
     * @return HasOne<AiSeoSuggestionReview, $this>
     */
    public function latestReview(): HasOne
    {
        return $this
            ->hasOne(
                AiSeoSuggestionReview::class,
                'ai_seo_suggestion_id',
            )
            ->latestOfMany(
                'id',
            );
    }
}
