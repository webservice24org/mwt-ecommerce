<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Data\CreateShipmentItemData;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Services\ShippingProviderAccountResolver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use App\Models\ShippingProviderAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateShipmentAction
{
    public function __construct(
        private ShippingProviderAccountResolver $providerAccounts,
    ) {}

    /**
     * @param  list<CreateShipmentItemData>  $items
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws ValidationException
     */
    public function execute(
        Order $order,
        ShippingMethod $shippingMethod,
        array $items,
        ?string $carrier = null,
        ?string $service = null,
        ?array $metadata = null,
        ShippingProviderCode $providerCode =
        ShippingProviderCode::Manual,
    ): Shipment {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'At least one shipment item is required.',
            ]);
        }

        return DB::transaction(
            function () use ($order, $shippingMethod, $items, $carrier, $service, $metadata, $providerCode): Shipment {
                $lockedOrder =
                    Order::query()
                        ->whereKey(
                            $order->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedShippingMethod =
                    ShippingMethod::query()
                        ->whereKey(
                            $shippingMethod
                                ->getKey(),
                        )
                        ->firstOrFail();

                $this->validateOrder(
                    $lockedOrder,
                );

                /*
                 * Only validate the commercial checkout
                 * service here.
                 *
                 * Its driver no longer decides which
                 * courier physically handles the parcel.
                 */
                $this->validateShippingMethod(
                    order: $lockedOrder,

                    shippingMethod: $lockedShippingMethod,
                );

                $providerAccount =
                    $this
                        ->resolveProviderAccount(
                            $providerCode,
                        );

                $requestedItems =
                    $this->normalizeItems(
                        $items,
                    );

                $orderItemIds =
                    array_map(
                        static fn (
                            CreateShipmentItemData $item,
                        ): int => $item
                            ->orderItemId,

                        $requestedItems,
                    );

                $orderItems =
                    OrderItem::query()
                        ->where(
                            'order_id',
                            $lockedOrder->id,
                        )
                        ->whereIn(
                            'id',
                            $orderItemIds,
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            'id',
                        );

                if (
                    $orderItems->count() !==
                    count(
                        $orderItemIds,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more selected order items do not belong to this order.',
                    ]);
                }

                foreach (
                    $requestedItems as $requestedItem
                ) {
                    $orderItem =
                        $orderItems->get(
                            $requestedItem
                                ->orderItemId,
                        );

                    if (
                        ! $orderItem instanceof OrderItem
                    ) {
                        throw ValidationException::withMessages([
                            'items' => 'One or more selected order items are invalid.',
                        ]);
                    }

                    $allocatedQuantity =
                        $this
                            ->allocatedQuantity(
                                $orderItem,
                            );

                    $remainingQuantity =
                        $orderItem->quantity -
                        $allocatedQuantity;

                    if (
                        $requestedItem->quantity >
                        $remainingQuantity
                    ) {
                        throw ValidationException::withMessages([
                            'items' => sprintf(
                                'Only %d unit(s) remain available for shipment for order item %d.',
                                max(
                                    0,
                                    $remainingQuantity,
                                ),
                                $orderItem->id,
                            ),
                        ]);
                    }
                }

                $shipment =
                    Shipment::query()
                        ->create([
                            'order_id' => $lockedOrder->id,

                            /*
                             * Checkout delivery service.
                             */
                            'shipping_method_id' => $lockedShippingMethod
                                ->id,

                            /*
                             * Physical courier choice.
                             */
                            'provider_code' => $providerCode
                                ->value,

                            'shipping_provider_account_id' => $providerAccount
                                ?->id,

                            'status' => ShipmentStatus::Pending,

                            'carrier' => $carrier,

                            'service' => $service,

                            'tracking_number' => null,

                            'tracking_url' => null,

                            'shipped_at' => null,

                            'delivered_at' => null,

                            'returned_at' => null,

                            'metadata' => $metadata,
                        ]);

                foreach (
                    $requestedItems as $requestedItem
                ) {
                    $shipment
                        ->items()
                        ->create([
                            'order_item_id' => $requestedItem
                                ->orderItemId,

                            'quantity' => $requestedItem
                                ->quantity,
                        ]);
                }

                return $shipment
                    ->load([
                        'shippingMethod',
                        'shippingProviderAccount',
                        'items',
                        'items.orderItem',
                    ]);
            },
        );
    }

    private function resolveProviderAccount(
        ShippingProviderCode $providerCode,
    ): ?ShippingProviderAccount {
        if (
            ! $providerCode
                ->requiresAccount()
        ) {
            return null;
        }

        $account =
            $this
                ->providerAccounts
                ->connected(
                    $providerCode,
                );

        if (
            ! $account instanceof ShippingProviderAccount
        ) {
            throw ValidationException::withMessages([
                'provider_code' => sprintf(
                    '%s is not connected and active.',
                    $providerCode->label(),
                ),
            ]);
        }

        return $account;
    }

    /**
     * @param  list<CreateShipmentItemData>  $items
     * @return list<CreateShipmentItemData>
     */
    private function normalizeItems(
        array $items,
    ): array {
        $seenOrderItemIds = [];

        foreach ($items as $item) {
            if (
                $item->orderItemId <= 0
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Every shipment item must reference a valid order item.',
                ]);
            }

            if (
                $item->quantity <= 0
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Shipment item quantities must be greater than zero.',
                ]);
            }

            if (
                isset(
                    $seenOrderItemIds[
                        $item->orderItemId
                    ],
                )
            ) {
                throw ValidationException::withMessages([
                    'items' => 'The same order item cannot appear more than once in a shipment.',
                ]);
            }

            $seenOrderItemIds[
                $item->orderItemId
            ] = true;
        }

        return $items;
    }

    private function validateOrder(
        Order $order,
    ): void {
        if (
            ! in_array(
                $order->status,
                [
                    OrderStatus::Pending,
                    OrderStatus::Confirmed,
                    OrderStatus::Processing,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'order' => 'Shipments cannot be created for this order state.',
            ]);
        }

        if (
            ! in_array(
                $order
                    ->fulfilment_status,
                [
                    FulfilmentStatus::Unfulfilled,
                    FulfilmentStatus::Processing,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'fulfilment_status' => 'Shipments cannot be created after fulfilment has already been shipped, delivered, or returned.',
            ]);
        }
    }

    private function validateShippingMethod(
        Order $order,
        ShippingMethod $shippingMethod,
    ): void {
        if (
            ! $shippingMethod
                ->is_active
        ) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'The shipping method is inactive.',
            ]);
        }

        /*
         * Keep the checkout service association.
         *
         * This protects the commercial order snapshot,
         * but says nothing about the physical courier.
         */
        if (
            $order->shipping_method_id !==
            null
            &&
            $order->shipping_method_id !==
            $shippingMethod->id
        ) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'The shipment checkout delivery service must match the order shipping method.',
            ]);
        }
    }

    private function allocatedQuantity(
        OrderItem $orderItem,
    ): int {
        return (int) ShipmentItem::query()
            ->where(
                'order_item_id',
                $orderItem->id,
            )
            ->whereHas(
                'shipment',
                static function ($query): void {
                    $query->where(
                        'status',
                        '!=',
                        ShipmentStatus::Cancelled
                            ->value,
                    );
                },
            )
            ->sum(
                'quantity',
            );
    }
}
