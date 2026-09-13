<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Models\Brand;
use Illuminate\Pagination\LengthAwarePaginator;

final class BrandIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginate(
        ?string $search = null,
        ?bool $isActive = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Brand::query()
            ->when(
                $search !== null &&
                $search !== '',
                static function ($query) use ($search): void {
                    $query->where(
                        static function ($query) use ($search): void {
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
                static fn ($query) => $query->where(
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
