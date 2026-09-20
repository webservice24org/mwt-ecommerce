<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateProductData;
use App\Domain\Catalog\Services\ProductCommercialIntegrityService;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateProductAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private ProductCommercialIntegrityService $commercialIntegrity,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(CreateProductData $data): Product
    {
        $this->commercialIntegrity
            ->assertProductDataIsValid(
                type: $data->type,
                sku: $data->sku,
                price: $data->price,
                compareAtPrice: $data->compareAtPrice,
            );

        $product = DB::transaction(function () use ($data): Product {
            $slug = $this->slugGenerator->generate(
                table: 'products',
                column: 'slug',
                value: $data->slug ?? $data->name,
            );

            $product = Product::query()->create([
                'brand_id' => $data->brandId,
                'type' => $data->type,
                'sku' => $data->sku,
                'price' => $data->price,
                'compare_at_price' => $data->compareAtPrice,
                'cost_price' => $data->costPrice,
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

            return $product->load([
                'brand',
                'categories',
            ]);
        });

        $this->storefrontCache->invalidate();

        return $product;
    }
}
