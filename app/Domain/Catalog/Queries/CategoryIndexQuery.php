<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

final class CategoryIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(
        ?string $search = null,
        ?bool $isActive = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Category::query()
            ->with([
                'parent:id,name',
            ]);

        if ($search !== null && $search !== '') {
            $query->where(
                function ($query) use ($search): void {
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
        }

        if ($isActive !== null) {
            $query->where(
                'is_active',
                $isActive,
            );
        }

        return $query
            ->orderBy('position')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
