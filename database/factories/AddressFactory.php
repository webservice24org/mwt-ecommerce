<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'label' => fake()->randomElement([
                'Home',
                'Office',
            ]),

            'recipient_name' => fake()->name(),

            'phone' => fake()->numerify(
                '01#########',
            ),

            'address_line_1' => fake()->streetAddress(),

            'address_line_2' => null,

            'city' => fake()->city(),

            'state_or_region' => fake()->state(),

            'postal_code' => fake()->postcode(),

            'country_code' => 'BD',

            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(
            fn (): array => [
                'is_default' => true,
            ],
        );
    }
}
