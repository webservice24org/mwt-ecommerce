<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
final class ShippingZoneFactory extends Factory
{
    protected $model = ShippingZone::class;

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

            'is_active' => true,

            'is_catch_all' => false,

            'priority' => 100,

            'metadata' => null,
        ];
    }
}
