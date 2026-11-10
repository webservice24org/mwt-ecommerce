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
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Queries\GetOrderStatusTimelineQuery;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Queries\GetAdminOrderShippingReadModelQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\CancelOrderRequest;
use App\Http\Requests\Admin\Orders\TransitionOrderRequest;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        GetAdminOrderShippingReadModelQuery $shippingReadModelQuery,
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

                        'due_at' => $order
                            ->payment_due_at
                            ?->toISOString(),

                        'paid_at' => $order
                            ->paid_at
                            ?->toISOString(),

                        'actions' => [
                            'can_mark_due' => $order->fulfilment_status ===
                                FulfilmentStatus::Delivered
                                &&
                                $order->payment_status ===
                                PaymentStatus::Unpaid,

                            'can_mark_paid' => $order->fulfilment_status ===
                                FulfilmentStatus::Delivered
                                &&
                                in_array(
                                    $order->payment_status,
                                    [
                                        PaymentStatus::Unpaid,
                                        PaymentStatus::Due,
                                    ],
                                    true,
                                ),

                            'due_url' => route(
                                'admin.orders.payment.due',
                                $order,
                            ),

                            'paid_url' => route(
                                'admin.orders.payment.paid',
                                $order,
                            ),
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

                    /*
                     * Immutable checkout shipping snapshot.
                     *
                     * Never replace these values with the current
                     * ShippingMethod / ShippingRate records.
                     */
                    'shipping_selection' => [
                        'method_id' => $order->shipping_method_id,

                        'method_code' => $order->shipping_method_code,

                        'method_name' => $order->shipping_method_name,

                        'rate_id' => $order->shipping_rate_id,

                        'rate_code' => $order->shipping_rate_code,

                        'rate_name' => $order->shipping_rate_name,

                        'delivery_total' => $order->delivery_total,
                    ],

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
                        'allowed' => $this
                            ->orderCancellationAllowed(
                                $order,
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

                    'shipping' => $shippingReadModelQuery
                        ->get(
                            $order,
                        ),
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
        $this->assertLegacyAggregateTransitionAllowed(
            order: $order,

            transition: 'ship',
        );

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
        $this->assertLegacyAggregateTransitionAllowed(
            order: $order,

            transition: 'deliver',
        );

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
        $this->assertOrderCancellationCompatibleWithShipments(
            $order,
        );

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
            FulfilmentStatus::Processing => $this->hasPhysicalShipments(
                $order,
            )
                ? []
                : [
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

            FulfilmentStatus::Shipped => $this->hasPhysicalShipments(
                $order,
            )
                ? []
                : [
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

            FulfilmentStatus::Delivered => in_array(
                $order
                    ->payment_status,
                [
                    PaymentStatus::Paid,
                    PaymentStatus::Due,
                ],
                true,
            )
                ? [
                [
                    'key' => 'complete',

                    'label' => 'Complete Order',

                    'description' => 'Close the successfully delivered order.',

                    'url' => route(
                        'admin.orders.complete',
                        $order,
                    ),
                ],
            ]
                : [],

            default => [],
        };
    }

    private function orderCancellationAllowed(
        Order $order,
    ): bool {
        return $order
            ->status
            ->canTransitionTo(
                OrderStatus::Cancelled,
            ) &&
            ! $this->hasActivePhysicalShipments(
                $order,
            );
    }

    private function assertOrderCancellationCompatibleWithShipments(
        Order $order,
    ): void {
        if (
            ! $this->hasActivePhysicalShipments(
                $order,
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'cancellation' => 'Cancel every active physical shipment before cancelling the order.',
        ]);
    }

    private function hasActivePhysicalShipments(
        Order $order,
    ): bool {
        return Shipment::query()
            ->where(
                'order_id',
                $order->id,
            )
            ->where(
                'status',
                '!=',
                ShipmentStatus::Cancelled
                    ->value,
            )
            ->exists();
    }

    private function assertLegacyAggregateTransitionAllowed(
        Order $order,
        string $transition,
    ): void {
        if (
            ! $this->hasPhysicalShipments(
                $order,
            )
        ) {
            return;
        }

        $message = match (
            $transition
        ) {
            'ship' => 'This order has physical shipments. Ship the remaining shipment quantities instead of changing aggregate fulfilment directly.',

            'deliver' => 'This order has physical shipments. Deliver the physical shipments instead of changing aggregate fulfilment directly.',

            default => 'This aggregate fulfilment transition is unavailable because physical shipments exist.',
        };

        throw ValidationException::withMessages([
            'fulfilment_status' => $message,
        ]);
    }

    private function hasPhysicalShipments(
        Order $order,
    ): bool {
        return Shipment::query()
            ->where(
                'order_id',
                $order->id,
            )
            ->exists();
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
