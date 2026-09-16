<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
final class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),

            'path' => 'catalog/products/test/images/'
                .$this->faker->uuid()
                .'.jpg',

            'original_name' => $this->faker->word()
                .'.jpg',

            'mime_type' => 'image/jpeg',

            'file_size' => $this->faker->numberBetween(
                10_000,
                2_000_000,
            ),

            'width' => $this->faker->numberBetween(
                800,
                2000,
            ),

            'height' => $this->faker->numberBetween(
                800,
                2000,
            ),

            'alt_text' => $this->faker->sentence(4),

            'position' => 0,

            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(
            fn (): array => [
                'is_primary' => true,
                'position' => 0,
            ],
        );
    }
}
