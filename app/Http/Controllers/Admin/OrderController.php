<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Actions\CancelOrderAction;
use App\Domain\Orders\Actions\CompleteOrderAction;
use App\Domain\Orders\Actions\ConfirmOrderAction;
use App\Domain\Orders\Actions\DeliverOrderAction;
use App\Domain\Orders\Actions\ShipOrderAction;
use App\Domain\Orders\Actions\StartOrderProcessingAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Queries\GetOrderStatusTimelineQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\CancelOrderRequest;
use App\Http\Requests\Admin\Orders\TransitionOrderRequest;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
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
        GetOrderStatusTimelineQuery $timelineQuery,
    ): Response {
        $order->load([
            'user:id,name,email',

            'invoice',

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

                    'timeline' => $timelineQuery
                        ->get(
                            $order,
                        ),

                    'available_actions' => $this
                        ->availableActions(
                            $order,
                        ),

                    'cancellation' => [
                        'allowed' => $order
                            ->status
                            ->canTransitionTo(
                                OrderStatus::Cancelled,
                            ),

                        'url' => route(
                            'admin.orders.cancel',
                            $order,
                        ),
                    ],

                    'invoice' => $order->invoice !== null
                        ? [
                            'number' => $order
                                ->invoice
                                ->invoice_number,

                            'url' => route(
                                'admin.invoices.show',
                                $order->invoice,
                            ),
                        ]
                        : null,
                ],
            ],
        );
    }

    public function confirm(
        TransitionOrderRequest $request,
        Order $order,
        ConfirmOrderAction $action,
    ): RedirectResponse {
        $action->execute(
            $order,
            $this->admin(
                $request,
            ),
            $request->note(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Order confirmed successfully.',
        );
    }

    public function startProcessing(
        TransitionOrderRequest $request,
        Order $order,
        StartOrderProcessingAction $action,
    ): RedirectResponse {
        $action->execute(
            $order,
            $this->admin(
                $request,
            ),
            $request->note(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Order processing started.',
        );
    }

    public function ship(
        TransitionOrderRequest $request,
        Order $order,
        ShipOrderAction $action,
    ): RedirectResponse {
        $action->execute(
            $order,
            $this->admin(
                $request,
            ),
            $request->note(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Order marked as shipped.',
        );
    }

    public function deliver(
        TransitionOrderRequest $request,
        Order $order,
        DeliverOrderAction $action,
    ): RedirectResponse {
        $action->execute(
            $order,
            $this->admin(
                $request,
            ),
            $request->note(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Order marked as delivered.',
        );
    }

    public function complete(
        TransitionOrderRequest $request,
        Order $order,
        CompleteOrderAction $action,
    ): RedirectResponse {
        $action->execute(
            $order,
            $this->admin(
                $request,
            ),
            $request->note(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Order completed successfully.',
        );
    }

    public function cancel(
        CancelOrderRequest $request,
        Order $order,
        CancelOrderAction $action,
    ): RedirectResponse {
        $action->execute(
            $order,
            $this->admin(
                $request,
            ),
            $request->reason(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Order cancelled successfully.',
        );
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     url: string
     * }>
     */
    private function availableActions(
        Order $order,
    ): array {
        if (
            $order->status ===
            OrderStatus::Pending
        ) {
            return [
                [
                    'key' => 'confirm',

                    'label' => 'Confirm Order',

                    'description' => 'Accept this customer order for fulfilment.',

                    'url' => route(
                        'admin.orders.confirm',
                        $order,
                    ),
                ],
            ];
        }

        if (
            $order->status ===
            OrderStatus::Confirmed
        ) {
            return [
                [
                    'key' => 'start_processing',

                    'label' => 'Start Processing',

                    'description' => 'Begin warehouse and fulfilment processing.',

                    'url' => route(
                        'admin.orders.start-processing',
                        $order,
                    ),
                ],
            ];
        }

        if (
            $order->status !==
            OrderStatus::Processing
        ) {
            return [];
        }

        return match (
            $order->fulfilment_status
        ) {
            FulfilmentStatus::Processing => [
                [
                    'key' => 'ship',

                    'label' => 'Mark Shipped',

                    'description' => 'Record that the order has left fulfilment.',

                    'url' => route(
                        'admin.orders.ship',
                        $order,
                    ),
                ],
            ],

            FulfilmentStatus::Shipped => [
                [
                    'key' => 'deliver',

                    'label' => 'Mark Delivered',

                    'description' => 'Confirm that the customer received the order.',

                    'url' => route(
                        'admin.orders.deliver',
                        $order,
                    ),
                ],
            ],

            FulfilmentStatus::Delivered => [
                [
                    'key' => 'complete',

                    'label' => 'Complete Order',

                    'description' => 'Close the successfully delivered order.',

                    'url' => route(
                        'admin.orders.complete',
                        $order,
                    ),
                ],
            ],

            default => [],
        };
    }

    private function admin(
        FormRequest $request,
    ): Admin {
        $admin = $request->user(
            'admin',
        );

        if (! $admin instanceof Admin) {
            abort(
                403,
            );
        }

        return $admin;
    }
}
