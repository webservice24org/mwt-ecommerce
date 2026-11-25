<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductCardData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class StorefrontBrandProductQuery
{
    public function __construct(
        private StorefrontProductListingQuery $listingQuery,
        private StorefrontProductDataFactory $productData,
    ) {}

    /**
     * @return LengthAwarePaginator<int, StorefrontProductCardData>
     */
    public function paginate(
        Brand $brand,
        StorefrontProductFiltersData $filters,
        int $perPage = 24,
    ): LengthAwarePaginator {
        $paginator =
            $this
                ->listingQuery
                ->build(
                    filters: $filters,

                    brand: $brand,
                )
                ->paginate(
                    $perPage,
                )
                ->withQueryString();

        return $paginator->through(
            fn (
                Product $product,
            ): StorefrontProductCardData => $this
                ->productData
                ->card(
                    $product,
                ),
        );
    }
}
