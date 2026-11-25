<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

final readonly class StorefrontRelatedProductsQuery
{
    private const LIMIT = 8;

    public function __construct(
        private StorefrontProductDataFactory $productData,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function get(Product $product): array
    {
        $product->loadMissing('categories');

        $categoryIds = $product->categories->modelKeys();

        $related = $this->query($product)
            ->whereHas('categories', static fn ($query) => $query
                ->whereIn('categories.id', $categoryIds)
                ->where('categories.is_active', true))
            ->limit(self::LIMIT)
            ->get();

        $remaining = self::LIMIT - $related->count();

        // Fill unused slots with the same active brand, never duplicates.
        if ($remaining > 0 && $product->brand_id !== null) {
            $sameBrand = $this->query($product)
                ->where('brand_id', $product->brand_id)
                ->whereHas('brand', static fn ($query) => $query->where('is_active', true))
                ->whereNotIn('products.id', $related->modelKeys())
                ->limit($remaining)
                ->get();

            $related = $related->merge($sameBrand);
        }

        return $related
            ->map(fn (Product $candidate): array => $this->productData->card($candidate)->toArray())
            ->values()
            ->all();
    }

    /**
     * @return Builder<Product>
     */
    private function query(Product $product): Builder
    {
        // Read live publication state; do not cache recommendations across
        // moderation, taxonomy or scheduled publication changes.
        return Product::query()
            ->published()
            ->where('products.id', '<>', $product->id)
            ->with([
                'brand',
                'primaryImage',
                'variants' => static fn ($query) => $query
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('position')
                    ->orderBy('id'),
            ])
            ->latest('published_at')
            ->latest('id');
    }
}
