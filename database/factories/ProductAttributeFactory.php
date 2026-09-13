<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductAttribute>
 */
final class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    public function definition(): array
    {
        $name = fake()
            ->unique()
            ->word();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'position' => 0,
            'is_active' => true,
        ];
    }
}
