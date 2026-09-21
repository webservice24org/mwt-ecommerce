<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class PageIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, Page>
     */
    public function paginate(
        ?string $search = null,
        ?PageStatus $status = null,
        ?PageType $type = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Page::query()
            ->withCount('sections')
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
                                    'title',
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
                $type !== null,
                static fn (
                    Builder $query,
                ): Builder => $query->where(
                    'type',
                    $type->value,
                ),
            )
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
