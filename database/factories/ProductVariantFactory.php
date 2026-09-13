<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
final class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(
                fake()->unique()->bothify(
                    'SKU-####-????',
                ),
            ),
            'name' => null,
            'price' => fake()->numberBetween(
                10000,
                500000,
            ),
            'compare_at_price' => null,
            'cost_price' => null,
            'barcode' => null,
            'position' => 0,
            'is_active' => true,
            'is_default' => true,
            'weight' => null,
        ];
    }
}
