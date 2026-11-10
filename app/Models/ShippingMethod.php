<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $driver
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, mixed>|null $metadata
 */
final class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'driver',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(
            Shipment::class,
        );
    }

    /**
     * @return HasMany<ShippingRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(
            ShippingRate::class,
        );
    }
}
