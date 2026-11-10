<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
final class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(
                2,
                true,
            ),

            'code' => fake()->unique()->slug(
                2,
            ),

            'driver' => 'manual',

            'is_active' => true,

            'sort_order' => 0,

            'metadata' => null,
        ];
    }
}
