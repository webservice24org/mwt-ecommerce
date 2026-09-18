<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

final class StorefrontProductDetailQuery
{
    public function findBySlugOrFail(
        string $slug,
    ): Product {
        return $this->query()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @return Builder<Product>
     */
    private function query(): Builder
    {
        return Product::query()
            ->published()
            ->with([
                'brand:id,name,slug',

                'categories' => static fn ($query) => $query
                    ->select([
                        'categories.id',
                        'categories.name',
                        'categories.slug',
                    ])
                    ->where('categories.is_active', true)
                    ->orderBy('categories.position')
                    ->orderBy('categories.name'),

                'images' => static fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('position')
                    ->orderBy('id'),

                'variants' => static fn ($query) => $query
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('position')
                    ->orderBy('id'),

                'variants.attributeValues' => static fn ($query) => $query
                    ->where('attribute_values.is_active', true)
                    ->orderBy('attribute_values.position')
                    ->orderBy('attribute_values.name'),

                'variants.attributeValues.attribute',
            ]);
    }
}
