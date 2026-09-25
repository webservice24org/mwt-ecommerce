<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Data\CatalogProductOptionData;
use App\Models\Product;

final class GetCatalogProductOptionsByIdsQuery
{
    /**
     * @param  list<int>  $ids
     * @return list<CatalogProductOptionData>
     */
    public function handle(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(
            array_unique($ids),
        );

        $products = Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'sku',
            ])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $options = [];

        foreach ($ids as $id) {
            /** @var Product|null $product */
            $product = $products->get($id);

            if ($product === null) {
                continue;
            }

            $options[] =
                new CatalogProductOptionData(
                    id: $product->id,
                    name: $product->name,
                    slug: $product->slug,
                    sku: $product->sku,
                );
        }

        return $options;
    }
}
