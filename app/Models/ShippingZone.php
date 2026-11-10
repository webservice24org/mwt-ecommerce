<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShippingZoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'is_catch_all',
        'priority',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_catch_all' => 'boolean',
            'priority' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<ShippingZoneRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(
            ShippingZoneRule::class,
        );
    }

    /**
     * @return BelongsToMany<ShippingRate, $this>
     */
    public function rates(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                ShippingRate::class,
                'shipping_rate_zone',
            )
            ->withTimestamps();
    }
}
