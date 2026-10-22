<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property string $type
 * @property string|null $from_status
 * @property string $to_status
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $note
 * @property array<string, mixed>|null $metadata
 * @property Carbon $changed_at
 */
final class OrderStatusHistory extends Model
{
    public const TYPE_ORDER =
        'order';

    public const TYPE_PAYMENT =
        'payment';

    public const TYPE_FULFILMENT =
        'fulfilment';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'type',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
        'metadata',
        'changed_at',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
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
            'metadata' => 'array',

            'changed_at' => 'datetime',
        ];
    }
}
