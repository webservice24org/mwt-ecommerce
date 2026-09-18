<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductCardData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class StorefrontCategoryProductQuery
{
    public function __construct(
        private StorefrontProductListingQuery $listingQuery,
        private StorefrontProductDataFactory $productData,
    ) {}

    /**
     * @return LengthAwarePaginator<int, StorefrontProductCardData>
     */
    public function paginate(
        Category $category,
        StorefrontProductFiltersData $filters,
        int $perPage = 24,
    ): LengthAwarePaginator {
        $paginator = $this
            ->listingQuery
            ->build(
                filters: $filters,
                category: $category,
            )
            ->paginate($perPage)
            ->withQueryString();

        return $paginator->through(
            fn (Product $product): StorefrontProductCardData => $this
                ->productData
                ->card($product),
        );
    }
}
