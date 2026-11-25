<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_crawler_policy_id
 * @property int $version
 * @property array<string, bool>|null $before_policy
 * @property array<string, bool> $after_policy
 * @property int|null $changed_by_admin_id
 * @property Carbon $created_at
 */
final class AiCrawlerPolicyEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_crawler_policy_id',
        'version',
        'before_policy',
        'after_policy',
        'changed_by_admin_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_crawler_policy_id' => 'integer',

            'version' => 'integer',

            'before_policy' => 'array',

            'after_policy' => 'array',

            'changed_by_admin_id' => 'integer',

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AiCrawlerPolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(
            AiCrawlerPolicy::class,
            'ai_crawler_policy_id',
        );
    }
}
