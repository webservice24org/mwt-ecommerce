<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Models\Product;
use Illuminate\Support\Collection;

final readonly class StorefrontProductSearchSuggestionQuery
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     slug: string
     * }>
     */
    public function get(
        string $search,
        int $limit = 6,
    ): Collection {
        $term = trim($search);

        if ($term === '') {
            return collect();
        }

        return Product::query()
            ->published()
            ->where(
                'name',
                'like',
                '%'.$term.'%',
            )
            ->orderByRaw(
                'CASE WHEN name LIKE ? THEN 0 ELSE 1 END',
                [$term.'%'],
            )
            ->orderBy('name')
            ->limit($limit)
            ->get([
                'id',
                'name',
                'slug',
            ])
            ->map(
                static fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                ],
            );
    }
}
