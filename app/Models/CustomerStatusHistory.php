<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $from_status
 * @property string $to_status
 * @property string|null $reason
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property Carbon $changed_at
 */
final class CustomerStatusHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'from_status',
        'to_status',
        'reason',
        'actor_type',
        'actor_id',
        'actor_name',
        'changed_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }
}
