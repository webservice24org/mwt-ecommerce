<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wishlist>
 */
final class WishlistFactory extends Factory
{
    protected $model =
        Wishlist::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'product_id' => Product::factory(),
        ];
    }
}
