<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(
            3,
            true,
        );

        return [
            'brand_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(
                1000,
                999999,
            ),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(
                3,
                true,
            ),
            'status' => ProductStatus::Draft,
            'is_featured' => false,
            'position' => 0,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ProductStatus::Published,
                'published_at' => now(),
            ],
        );
    }

    public function featured(): static
    {
        return $this->state(
            fn (): array => [
                'is_featured' => true,
            ],
        );
    }

    public function withBrand(): static
    {
        return $this->state(
            fn (): array => [
                'brand_id' => Brand::factory(),
            ],
        );
    }
}
