<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class AttributeIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, ProductAttribute>
     */
    public function paginate(
        ?string $search = null,
        ?bool $isActive = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return ProductAttribute::query()
            ->withCount('values')
            ->when(
                $search !== null
                    && $search !== '',
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
                $isActive !== null,
                static fn (
                    Builder $query,
                ): Builder => $query->where(
                    'is_active',
                    $isActive,
                ),
            )
            ->orderBy('position')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
