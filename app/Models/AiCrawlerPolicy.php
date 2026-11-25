<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $scope_key
 * @property bool $allow_oai_search_bot
 * @property bool $allow_gpt_bot
 * @property bool $allow_google_extended
 * @property int $version
 * @property int|null $updated_by_admin_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class AiCrawlerPolicy extends Model
{
    protected $fillable = [
        'scope_key',
        'allow_oai_search_bot',
        'allow_gpt_bot',
        'allow_google_extended',
        'version',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'allow_oai_search_bot' => 'boolean',

            'allow_gpt_bot' => 'boolean',

            'allow_google_extended' => 'boolean',

            'version' => 'integer',

            'updated_by_admin_id' => 'integer',
        ];
    }

    /**
     * @return HasMany<AiCrawlerPolicyEvent, $this>
     */
    public function events(): HasMany
    {
        return $this
            ->hasMany(
                AiCrawlerPolicyEvent::class,
                'ai_crawler_policy_id',
            )
            ->orderByDesc(
                'version',
            );
    }
}
