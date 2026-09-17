<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateProductData;
use App\Domain\Catalog\Services\ProductCommercialIntegrityService;
use App\Models\Product;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProductAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private ProductCommercialIntegrityService $commercialIntegrity,
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
                $lockedProduct = Product::query()
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->commercialIntegrity
                    ->assertProductDataIsValid(
                        type: $data->type,
                        sku: $data->sku,
                        price: $data->price,
                        compareAtPrice: $data->compareAtPrice,
                        ignoreProductId: $lockedProduct->id,
                    );

                $this->commercialIntegrity
                    ->assertProductTypeTransitionIsValid(
                        product: $lockedProduct,
                        requestedType: $data->type,
                    );

                $this->commercialIntegrity
                    ->assertProductPricingIsValidForExistingVariants(
                        product: $lockedProduct,
                        proposedPrice: $data->price,
                        proposedCompareAtPrice: $data->compareAtPrice,
                    );

                $slug = $this->slugGenerator->generate(
                    table: 'products',
                    column: 'slug',
                    value: $data->slug ?? $data->name,
                    ignoreId: $lockedProduct->id,
                );

                $lockedProduct->update([
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

                $lockedProduct->categories()->sync(
                    $data->categoryIds,
                );

                return $lockedProduct
                    ->refresh()
                    ->load([
                        'brand',
                        'categories',
                    ]);
            },
        );
    }
}
