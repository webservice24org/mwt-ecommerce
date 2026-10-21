<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Domain\Orders\Queries\FindCustomerOrderQuery;
use App\Domain\Orders\Queries\ListCustomerOrdersQuery;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerOrderController extends Controller
{
    public function index(
        Request $request,
        ListCustomerOrdersQuery $query,
    ): Response {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $orders = $query->execute(
            $user,
        );

        return Inertia::render(
            'Customer/Orders/Index',
            [
                'orders' => $orders->through(
                    static fn (
                        Order $order,
                    ): array => [
                        'id' => $order->id,

                        'order_number' => $order
                            ->order_number,

                        'status' => [
                            'value' => $order
                                ->status
                                ->value,

                            'label' => $order
                                ->status
                                ->label(),
                        ],

                        'currency_code' => $order
                            ->currency_code,

                        'grand_total' => $order
                            ->grand_total,

                        'items_count' => $order
                            ->items_count
                            ?? 0,

                        'placed_at' => $order
                            ->placed_at
                            ?->toISOString(),
                    ],
                ),
            ],
        );
    }

    public function show(
        Request $request,
        int $order,
        FindCustomerOrderQuery $query,
    ): Response {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $customerOrder = $query->execute(
            user: $user,
            orderId: $order,
        );

        return Inertia::render(
            'Customer/Orders/Show',
            [
                'order' => [
                    'id' => $customerOrder->id,

                    'order_number' => $customerOrder
                        ->order_number,

                    'status' => [
                        'value' => $customerOrder
                            ->status
                            ->value,

                        'label' => $customerOrder
                            ->status
                            ->label(),
                    ],

                    'payment' => [
                        'method' => [
                            'value' => $customerOrder
                                ->payment_method
                                ->value,

                            'label' => $customerOrder
                                ->payment_method
                                ->label(),
                        ],

                        'status' => [
                            'value' => $customerOrder
                                ->payment_status
                                ->value,

                            'label' => $customerOrder
                                ->payment_status
                                ->label(),
                        ],
                    ],

                    'fulfilment_status' => [
                        'value' => $customerOrder
                            ->fulfilment_status
                            ->value,

                        'label' => $customerOrder
                            ->fulfilment_status
                            ->label(),
                    ],

                    'currency_code' => $customerOrder
                        ->currency_code,

                    'subtotal' => $customerOrder
                        ->subtotal,

                    'discount_total' => $customerOrder
                        ->discount_total,

                    'delivery_total' => $customerOrder
                        ->delivery_total,

                    'tax_total' => $customerOrder
                        ->tax_total,

                    'grand_total' => $customerOrder
                        ->grand_total,

                    'placed_at' => $customerOrder
                        ->placed_at
                        ?->toISOString(),

                    'delivery_address' => [
                        'recipient_name' => $customerOrder
                            ->delivery_recipient_name,

                        'phone' => $customerOrder
                            ->delivery_phone,

                        'address_line_1' => $customerOrder
                            ->delivery_address_line_1,

                        'address_line_2' => $customerOrder
                            ->delivery_address_line_2,

                        'city' => $customerOrder
                            ->delivery_city,

                        'state_or_region' => $customerOrder
                            ->delivery_state_or_region,

                        'postal_code' => $customerOrder
                            ->delivery_postal_code,

                        'country_code' => $customerOrder
                            ->delivery_country_code,
                    ],

                    'items' => $customerOrder
                        ->items
                        ->map(
                            static fn (
                                OrderItem $item,
                            ): array => [
                                'id' => $item->id,

                                /*
                                         * Historical references are useful
                                         * internally, but presentation comes
                                         * exclusively from snapshot fields.
                                         */
                                'product_id' => $item
                                    ->product_id,

                                'variant_id' => $item
                                    ->variant_id,

                                'product_name' => $item
                                    ->product_name,

                                'sku' => $item
                                    ->sku,

                                'variant_description' => $item
                                    ->variant_description,

                                'unit_price' => $item
                                    ->unit_price,

                                'quantity' => $item
                                    ->quantity,

                                'line_total' => $item
                                    ->line_total,
                            ],
                        )
                        ->values()
                        ->all(),
                ],
            ],
        );
    }
}
