<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
final class AdminFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
            'role' => AdminRole::Admin,
        ];

    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => [
            'role' => AdminRole::SuperAdmin,
        ]);
    }
}
