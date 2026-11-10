<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShipmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShipmentItem extends Model
{
    /** @use HasFactory<ShipmentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'order_item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(
            Shipment::class,
        );
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            OrderItem::class,
        );
    }
}
