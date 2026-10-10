<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Queries;

use App\Domain\Catalog\Data\Storefront\StorefrontProductCardData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Domain\Wishlist\Services\WishlistService;
use App\Models\Product;

final readonly class StorefrontWishlistQuery
{
    public function __construct(
        private WishlistService $wishlist,
        private StorefrontProductDataFactory $productData,
    ) {}

    /**
     * @return array<int, StorefrontProductCardData>
     */
    public function get(): array
    {
        $ids = $this->wishlist->ids();

        if ($ids === []) {
            return [];
        }

        return Product::query()
            ->published()
            ->whereIn('id', $ids)
            ->with([
                'brand:id,name,slug',

                'images' => static fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('position')
                    ->orderBy('id'),

                'variants' => static fn ($query) => $query
                    ->where('is_active', true)
                    ->select([
                        'id',
                        'product_id',
                        'price',
                        'compare_at_price',
                        'cost_price',
                        'is_active',
                    ]),
            ])
            ->get()
            ->sortBy(
                static fn (Product $product): int => array_search(
                    $product->id,
                    $ids,
                    true,
                ),
            )
            ->map(
                fn (Product $product): StorefrontProductCardData => $this
                    ->productData
                    ->card($product),
            )
            ->values()
            ->all();
    }
}
