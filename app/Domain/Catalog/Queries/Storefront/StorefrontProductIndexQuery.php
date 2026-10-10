<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductCardData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class StorefrontProductIndexQuery
{
    public function __construct(
        private StorefrontProductListingQuery $listingQuery,
        private StorefrontProductDataFactory $productData,
    ) {}

    /**
     * @return LengthAwarePaginator<int, StorefrontProductCardData>
     */
    public function paginate(
        StorefrontProductFiltersData $filters,
        int $perPage = 24,
        ?string $search = null,
    ): LengthAwarePaginator {
        $normalizedSearch = trim((string) $search);

        $query = $this
            ->listingQuery
            ->build($filters);

        if ($normalizedSearch !== '') {
            $query->where(
                function ($query) use ($normalizedSearch): void {
                    $query->where(
                        'name',
                        'like',
                        '%'.$normalizedSearch.'%',
                    );
                },
            );
        }

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString();

        return $paginator->through(
            fn (Product $product): StorefrontProductCardData => $this
                ->productData
                ->card($product),
        );
    }
}
