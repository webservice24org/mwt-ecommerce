<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use Database\Factories\ShippingZoneRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShippingZoneRule extends Model
{
    /** @use HasFactory<ShippingZoneRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'shipping_zone_id',
        'type',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'type' => ShippingZoneRuleType::class,
        ];
    }

    public function ruleType(): ShippingZoneRuleType
    {
        $type = $this->getAttribute('type');

        if ($type instanceof ShippingZoneRuleType) {
            return $type;
        }

        return ShippingZoneRuleType::from(
            (string) $type,
        );
    }

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(
            ShippingZone::class,
        );
    }
}
