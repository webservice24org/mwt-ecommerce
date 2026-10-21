<?php

declare(strict_types=1);

namespace App\Domain\Orders\Queries;

use App\Models\Order;
use App\Models\User;

final class FindCustomerOrderQuery
{
    public function execute(
        User $user,
        int $orderId,
    ): Order {
        return $user
            ->orders()
            ->with([
                'items' => static fn ($query) => $query->orderBy('id'),
            ])
            ->whereKey(
                $orderId,
            )
            ->firstOrFail();
    }
}
