<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeValue>
 */
final class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        $name = fake()
            ->unique()
            ->word();

        return [
            'attribute_id' => ProductAttribute::factory(),

            'name' => Str::title($name),

            'slug' => Str::slug($name),

            'position' => 0,

            'is_active' => true,
        ];
    }
}
