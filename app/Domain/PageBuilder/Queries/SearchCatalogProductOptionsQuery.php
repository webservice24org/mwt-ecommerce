<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Data\CatalogProductOptionData;
use App\Models\Product;

final class SearchCatalogProductOptionsQuery
{
    private const MAX_RESULTS = 20;

    /**
     * @return list<CatalogProductOptionData>
     */
    public function handle(
        string $search,
        int $limit = self::MAX_RESULTS,
    ): array {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $limit = max(
            1,
            min($limit, self::MAX_RESULTS),
        );

        return Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'sku',
            ])
            ->where(function ($query) use ($search): void {
                $query
                    ->where(
                        'name',
                        'like',
                        '%'.$search.'%',
                    )
                    ->orWhere(
                        'sku',
                        'like',
                        '%'.$search.'%',
                    )
                    ->orWhere(
                        'slug',
                        'like',
                        '%'.$search.'%',
                    );
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(
                static fn (Product $product): CatalogProductOptionData => new CatalogProductOptionData(
                    id: $product->id,
                    name: $product->name,
                    slug: $product->slug,
                    sku: $product->sku,
                ),
            )
            ->all();
    }
}
