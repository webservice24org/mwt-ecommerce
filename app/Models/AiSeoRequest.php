<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property AiSeoEntityType $entity_type
 * @property int $entity_id
 * @property AiSeoAction $action
 * @property AiSeoRequestStatus $status
 * @property int|null $requested_by_admin_id
 * @property array<string, mixed> $input_snapshot
 * @property string $input_hash
 * @property string|null $provider
 * @property string|null $model_name
 * @property string|null $prompt_version
 * @property array<string, mixed>|null $raw_response
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 */
final class AiSeoRequest extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'status',
        'requested_by_admin_id',
        'input_snapshot',
        'input_hash',
        'provider',
        'model_name',
        'prompt_version',
        'raw_response',
        'error_code',
        'error_message',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_type' => AiSeoEntityType::class,

            'action' => AiSeoAction::class,

            'status' => AiSeoRequestStatus::class,

            'entity_id' => 'integer',

            'requested_by_admin_id' => 'integer',

            'input_snapshot' => 'array',

            'raw_response' => 'array',

            'started_at' => 'datetime',

            'completed_at' => 'datetime',

            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AiSeoSuggestion, $this>
     */
    public function suggestions(): HasMany
    {
        return $this
            ->hasMany(
                AiSeoSuggestion::class,
            )
            ->orderBy(
                'position',
            )
            ->orderBy(
                'id',
            );
    }
}
