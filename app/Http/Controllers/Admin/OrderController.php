<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OrderController extends Controller
{
    public function index(
        Request $request,
    ): Response {
        $orders = Order::query()
            ->with([
                'user:id,name,email',
            ])
            ->latest(
                'placed_at',
            )
            ->latest(
                'id',
            )
            ->paginate(
                20,
            )
            ->withQueryString()
            ->through(
                static fn (
                    Order $order,
                ): array => [
                    'id' => $order->id,

                    'order_number' => $order->order_number,

                    'customer' => [
                        'name' => $order
                            ->user
                            ?->name,

                        'email' => $order
                            ->user
                            ?->email,
                    ],

                    'status' => [
                        'value' => $order
                            ->status
                            ->value,

                        'label' => $order
                            ->status
                            ->label(),
                    ],

                    'payment_method' => [
                        'value' => $order
                            ->payment_method
                            ->value,

                        'label' => $order
                            ->payment_method
                            ->label(),
                    ],

                    'payment_status' => [
                        'value' => $order
                            ->payment_status
                            ->value,

                        'label' => $order
                            ->payment_status
                            ->label(),
                    ],

                    'fulfilment_status' => [
                        'value' => $order
                            ->fulfilment_status
                            ->value,

                        'label' => $order
                            ->fulfilment_status
                            ->label(),
                    ],

                    'currency_code' => $order->currency_code,

                    'grand_total' => $order->grand_total,

                    'placed_at' => $order
                        ->placed_at
                        ?->toISOString(),
                ],
            );

        return Inertia::render(
            'Admin/Orders/Index',
            [
                'orders' => $orders,
            ],
        );
    }

    public function show(
        Order $order,
    ): Response {
        $order->load([
            'user:id,name,email',
            'items' => static fn (
                $query,
            ) => $query
                ->orderBy(
                    'id',
                ),
        ]);

        return Inertia::render(
            'Admin/Orders/Show',
            [
                'order' => [
                    'id' => $order->id,

                    'order_number' => $order->order_number,

                    'customer' => [
                        'name' => $order
                            ->user
                            ?->name,

                        'email' => $order
                            ->user
                            ?->email,
                    ],

                    'status' => [
                        'value' => $order
                            ->status
                            ->value,

                        'label' => $order
                            ->status
                            ->label(),
                    ],

                    'payment' => [
                        'method' => [
                            'value' => $order
                                ->payment_method
                                ->value,

                            'label' => $order
                                ->payment_method
                                ->label(),
                        ],

                        'status' => [
                            'value' => $order
                                ->payment_status
                                ->value,

                            'label' => $order
                                ->payment_status
                                ->label(),
                        ],
                    ],

                    'fulfilment_status' => [
                        'value' => $order
                            ->fulfilment_status
                            ->value,

                        'label' => $order
                            ->fulfilment_status
                            ->label(),
                    ],

                    'currency_code' => $order->currency_code,

                    'subtotal' => $order->subtotal,

                    'discount_total' => $order->discount_total,

                    'delivery_total' => $order->delivery_total,

                    'tax_total' => $order->tax_total,

                    'grand_total' => $order->grand_total,

                    'placed_at' => $order
                        ->placed_at
                        ?->toISOString(),

                    'delivery_address' => [
                        'recipient_name' => $order
                            ->delivery_recipient_name,

                        'phone' => $order
                            ->delivery_phone,

                        'address_line_1' => $order
                            ->delivery_address_line_1,

                        'address_line_2' => $order
                            ->delivery_address_line_2,

                        'city' => $order
                            ->delivery_city,

                        'state_or_region' => $order
                            ->delivery_state_or_region,

                        'postal_code' => $order
                            ->delivery_postal_code,

                        'country_code' => $order
                            ->delivery_country_code,
                    ],

                    'items' => $order
                        ->items
                        ->map(
                            static fn (
                                $item,
                            ): array => [
                                'id' => $item->id,

                                'product_name' => $item
                                    ->product_name,

                                'sku' => $item->sku,

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
