<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $action
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string $reason
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 */
final class CustomerAccountAudit extends Model
{
    public const ACTION_DELETED =
        'deleted';

    public const ACTION_ANONYMIZED =
        'anonymized';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'action',
        'actor_type',
        'actor_id',
        'actor_name',
        'reason',
        'metadata',
        'occurred_at',
    ];

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
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
