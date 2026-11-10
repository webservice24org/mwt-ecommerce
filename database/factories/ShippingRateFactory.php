<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRate>
 */
final class ShippingRateFactory extends Factory
{
    protected $model = ShippingRate::class;

    public function definition(): array
    {
        return [
            'shipping_method_id' => ShippingMethod::factory(),

            'name' => 'Standard Rate',

            'code' => fake()->unique()->slug(
                2,
            ),

            'amount' => 80,

            'min_order_amount' => null,

            'max_order_amount' => null,

            'free_shipping_threshold' => null,

            'is_active' => true,

            'priority' => 0,

            'metadata' => null,
        ];
    }
}
