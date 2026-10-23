<?php

declare(strict_types=1);

namespace App\Domain\Customer\Queries;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class CustomerDetailQuery
{
    /**
     * @return array{
     *     customer: User,
     *     addresses: Collection<int, Address>,
     *     orders: LengthAwarePaginator<int, Order>
     * }
     */
    public function execute(
        User $customer,
    ): array {
        $customer->loadCount([
            'orders',
            'addresses',
        ]);

        $addresses = $customer
            ->addresses()
            ->select([
                'id',
                'user_id',
                'label',
                'recipient_name',
                'phone',
                'address_line_1',
                'address_line_2',
                'city',
                'state_or_region',
                'postal_code',
                'country_code',
                'is_default',
            ])
            ->orderByDesc(
                'is_default',
            )
            ->latest('id')
            ->get();

        $orders = $customer
            ->orders()
            ->select([
                'id',
                'user_id',
                'order_number',
                'status',
                'payment_status',
                'fulfilment_status',
                'currency_code',
                'grand_total',
                'placed_at',
            ])
            ->latest(
                'placed_at',
            )
            ->latest(
                'id',
            )
            ->paginate(
                perPage: 10,
                pageName: 'orders_page',
            )
            ->withQueryString();

        return [
            'customer' => $customer,
            'addresses' => $addresses,
            'orders' => $orders,
        ];
    }
}
