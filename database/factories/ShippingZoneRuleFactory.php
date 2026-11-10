<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZoneRule>
 */
final class ShippingZoneRuleFactory extends Factory
{
    protected $model = ShippingZoneRule::class;

    public function definition(): array
    {
        return [
            'shipping_zone_id' => ShippingZone::factory(),

            'type' => ShippingZoneRuleType::CountryCode,

            'value' => 'BD',
        ];
    }
}
