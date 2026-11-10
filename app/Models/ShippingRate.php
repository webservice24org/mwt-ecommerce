<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShippingRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class ShippingRate extends Model
{
    /** @use HasFactory<ShippingRateFactory> */
    use HasFactory;

    protected $fillable = [
        'shipping_method_id',
        'name',
        'code',
        'amount',
        'min_order_amount',
        'max_order_amount',
        'free_shipping_threshold',
        'is_active',
        'priority',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'min_order_amount' => 'integer',
            'max_order_amount' => 'integer',
            'free_shipping_threshold' => 'integer',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(
            ShippingMethod::class,
        );
    }

    /**
     * @return BelongsToMany<ShippingZone, $this>
     */
    public function zones(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                ShippingZone::class,
                'shipping_rate_zone',
            )
            ->withTimestamps();
    }
}
