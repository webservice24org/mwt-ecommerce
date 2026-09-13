<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(
        ?string $search = null,
        ?ProductStatus $status = null,
        ?int $brandId = null,
        ?bool $isFeatured = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Product::query()
            ->with([
                'brand:id,name',
            ])
            ->withCount([
                'categories',
                'variants',
            ])
            ->when(
                $search !== null && $search !== '',
                static function (
                    Builder $query,
                ) use ($search): void {
                    $query->where(
                        static function (
                            Builder $query,
                        ) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    '%'.$search.'%',
                                );
                        },
                    );
                },
            )
            ->when(
                $status !== null,
                static fn (
                    Builder $query,
                ): Builder => $query->where(
                    'status',
                    $status->value,
                ),
            )
            ->when(
                $brandId !== null,
                static fn (
                    Builder $query,
                ): Builder => $query->where(
                    'brand_id',
                    $brandId,
                ),
            )
            ->when(
                $isFeatured !== null,
                static fn (
                    Builder $query,
                ): Builder => $query->where(
                    'is_featured',
                    $isFeatured,
                ),
            )
            ->orderBy('position')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
