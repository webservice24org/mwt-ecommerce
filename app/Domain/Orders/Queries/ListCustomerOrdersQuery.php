<?php

declare(strict_types=1);

namespace App\Domain\Orders\Queries;

use App\Models\Order;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListCustomerOrdersQuery
{
    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function execute(
        User $user,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return $user
            ->orders()
            ->withCount(
                'items',
            )
            ->orderByDesc(
                'placed_at',
            )
            ->orderByDesc(
                'id',
            )
            ->paginate(
                $perPage,
            )
            ->withQueryString();
    }
}
