<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class PublishedProductQuery
{
    /**
     * @return Builder<Product>
     */
    private function query(): Builder
    {
        return Product::query()
            ->where(
                'status',
                ProductStatus::Published->value,
            )
            ->where(
                static function (
                    Builder $query,
                ): void {
                    $query
                        ->whereNull('published_at')
                        ->orWhere(
                            'published_at',
                            '<=',
                            now(),
                        );
                },
            );
    }

    public function findBySlug(
        string $slug,
    ): ?Product {
        return $this->query()
            ->with([
                'brand',
                'categories',
                'variants' => static fn (
                    $query,
                ) => $query
                    ->where('is_active', true)
                    ->orderBy('position'),
                'images',
            ])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(
        int $perPage = 24,
    ): LengthAwarePaginator {
        return $this->query()
            ->with([
                'brand:id,name,slug',
            ])
            ->with([
                'images' => static fn (
                    $query,
                ) => $query
                    ->where('is_primary', true)
                    ->orderBy('position'),
            ])
            ->orderBy('position')
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }
}
