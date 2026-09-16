<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\ProductVideoType;
use App\Models\Product;
use App\Models\ProductVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVideo>
 */
final class ProductVideoFactory extends Factory
{
    protected $model = ProductVideo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),

            'type' => ProductVideoType::Youtube,

            'path' => null,

            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',

            'original_name' => null,

            'mime_type' => null,

            'file_size' => null,

            'title' => $this->faker->sentence(3),
        ];
    }

    public function uploaded(): static
    {
        return $this->state(
            fn (): array => [
                'type' => ProductVideoType::Upload,

                'path' => 'catalog/products/test/videos/test.mp4',

                'url' => null,

                'original_name' => 'test.mp4',

                'mime_type' => 'video/mp4',

                'file_size' => 1_000_000,
            ],
        );
    }
}
