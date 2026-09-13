<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateProductData;
use App\Models\Product;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProductAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
    ) {}

    public function execute(
        Product $product,
        UpdateProductData $data,
    ): Product {
        return DB::transaction(
            function () use (
                $product,
                $data,
            ): Product {
                $slug = $this->slugGenerator->generate(
                    table: 'products',
                    column: 'slug',
                    value: $data->slug ?? $data->name,
                    ignoreId: $product->id,
                );

                $product->update([
                    'brand_id' => $data->brandId,
                    'name' => $data->name,
                    'slug' => $slug,
                    'short_description' => $data->shortDescription,
                    'description' => $data->description,
                    'status' => $data->status,
                    'is_featured' => $data->isFeatured,
                    'position' => $data->position,
                    'published_at' => $data->publishedAt,
                    'meta_title' => $data->metaTitle,
                    'meta_description' => $data->metaDescription,
                ]);

                $product->categories()->sync(
                    $data->categoryIds,
                );

                return $product
                    ->refresh()
                    ->load([
                        'brand',
                        'categories',
                    ]);
            },
        );
    }
}
