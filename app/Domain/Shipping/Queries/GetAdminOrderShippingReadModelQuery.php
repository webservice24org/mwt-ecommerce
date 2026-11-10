<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\SubmitShipmentToProviderAction;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\ShippingProviderAccount;
use Illuminate\Support\Facades\DB;

final class GetAdminOrderShippingReadModelQuery
{
    public function __construct(
        private readonly ShippingProviderRegistry $providers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(
        Order $order,
    ): array {
        $orderItems = OrderItem::query()
            ->where(
                'order_id',
                $order->id,
            )
            ->orderBy(
                'id',
            )
            ->get();

        /** @var array<int, OrderItem> $orderItemsById */
        $orderItemsById = [];

        foreach ($orderItems as $orderItem) {
            $orderItemsById[
                $orderItem->id
            ] = $orderItem;
        }

        $allocatedQuantities = DB::table(
            'shipment_items',
        )
            ->join(
                'shipments',
                'shipments.id',
                '=',
                'shipment_items.shipment_id',
            )
            ->where(
                'shipments.order_id',
                $order->id,
            )
            ->where(
                'shipments.status',
                '!=',
                ShipmentStatus::Cancelled
                    ->value,
            )
            ->selectRaw(
                'shipment_items.order_item_id AS order_item_id',
            )
            ->selectRaw(
                'SUM(shipment_items.quantity) AS allocated_quantity',
            )
            ->groupBy(
                'shipment_items.order_item_id',
            )
            ->pluck(
                'allocated_quantity',
                'order_item_id',
            );

        $allocationItems = [];
        $totalOrderedQuantity = 0;
        $totalAllocatedQuantity = 0;
        $totalRemainingQuantity = 0;
        $hasRemainingQuantity = false;

        foreach ($orderItems as $orderItem) {
            $orderedQuantity =
                $orderItem->quantity;

            $activeAllocatedQuantity = (int) (
                $allocatedQuantities->get(
                    $orderItem->id,
                ) ?? 0
            );

            $remainingQuantity = max(
                0,
                $orderedQuantity -
                $activeAllocatedQuantity,
            );

            $totalOrderedQuantity +=
                $orderedQuantity;

            $totalAllocatedQuantity +=
                $activeAllocatedQuantity;

            $totalRemainingQuantity +=
                $remainingQuantity;

            if ($remainingQuantity > 0) {
                $hasRemainingQuantity = true;
            }

            $allocationItems[] = [
                'order_item_id' => $orderItem->id,

                'product_name' => $orderItem->product_name,

                'sku' => $orderItem->sku,

                'variant_description' => $orderItem
                    ->variant_description,

                'ordered_quantity' => $orderedQuantity,

                'active_allocated_quantity' => $activeAllocatedQuantity,

                'remaining_quantity' => $remainingQuantity,
            ];
        }

        $selectedShippingMethodId =
            $this->nullableIntAttribute(
                $order,
                'shipping_method_id',
            );

        $shippingMethodQuery =
            ShippingMethod::query()
                ->where(
                    'is_active',
                    true,
                )
                ->orderBy(
                    'sort_order',
                )
                ->orderBy(
                    'id',
                );

        if (
            $selectedShippingMethodId !== null
        ) {
            $shippingMethodQuery->whereKey(
                $selectedShippingMethodId,
            );
        }

        $shippingMethodOptions =
            $shippingMethodQuery
                ->get([
                    'id',
                    'name',
                    'code',
                    'driver',
                ])
                ->map(
                    static fn (
                        ShippingMethod $method,
                    ): array => [
                        'id' => $method->id,
                        'name' => $method->name,
                        'code' => $method->code,
                    ],
                )
                ->values()
                ->all();

        $shipments = Shipment::query()
            ->with([
                'shippingMethod:id,name,code,driver',
                'shippingProviderAccount:id,provider_code,name,is_active,is_default,auto_submit,connection_status',
                'items',
                'providerSubmission',
            ])
            ->where(
                'order_id',
                $order->id,
            )
            ->orderBy(
                'id',
            )
            ->get()
            ->map(
                function (Shipment $shipment) use ($order, $orderItemsById): array {
                    $shipmentItems = $shipment
                        ->items
                        ->sortBy(
                            'id',
                        )
                        ->map(
                            static function (ShipmentItem $shipmentItem) use ($orderItemsById): array {
                                $orderItem =
                                    $orderItemsById[
                                        $shipmentItem
                                            ->order_item_id
                                    ] ?? null;

                                return [
                                    'id' => $shipmentItem->id,

                                    'order_item_id' => $shipmentItem
                                        ->order_item_id,

                                    'quantity' => $shipmentItem
                                        ->quantity,

                                    'product_name' => $orderItem
                                        ?->product_name,

                                    'sku' => $orderItem
                                        ?->sku,

                                    'variant_description' => $orderItem
                                        ?->variant_description,
                                ];
                            },
                        )
                        ->values()
                        ->all();

                    $submission =
                        $shipment
                            ->providerSubmission;

                    /*
                     * A provider submission is an immutable historical
                     * provider snapshot. Otherwise use the shipment's
                     * explicit 8.8C courier provider, with the model's
                     * legacy driver fallback for older shipments.
                     */
                    $providerCode =
                        $submission instanceof ShipmentProviderSubmission
                            ? $submission->provider_code
                            : $shipment->effectiveProviderCode();

                    $providerRegistered =
                        is_string(
                            $providerCode,
                        ) &&
                        $providerCode !== '' &&
                        $this->providers->has(
                            $providerCode,
                        );

                    $providerExternal =
                        is_string(
                            $providerCode,
                        ) &&
                        $providerCode !== '' &&
                        $providerCode !==
                        ManualShippingProvider::CODE;

                    $submissionMetadata =
                        $submission instanceof ShipmentProviderSubmission
                        &&
                        is_array(
                            $submission->metadata,
                        )
                        ? $submission->metadata
                        : [];

                    $submissionTrigger =
                        $this->submissionTrigger(
                            $submissionMetadata[
                                'submission_trigger'
                            ] ?? null,
                        );

                    $lastSubmissionTrigger =
                        $this->submissionTrigger(
                            $submissionMetadata[
                                'last_submission_trigger'
                            ] ?? null,
                        );

                    $providerAccount =
                        $shipment
                            ->shippingProviderAccount;

                    return [
                        'id' => $shipment->id,

                        'status' => [
                            'value' => $shipment
                                ->status
                                ->value,

                            'label' => $shipment
                                ->status
                                ->label(),
                        ],

                        'shipping_method' => $shipment->shippingMethod !== null
                            ? [
                                'id' => $shipment
                                    ->shippingMethod
                                    ->id,

                                'name' => $shipment
                                    ->shippingMethod
                                    ->name,

                                'code' => $shipment
                                    ->shippingMethod
                                    ->code,
                            ]
                            : null,

                        'carrier' => $shipment->carrier,

                        'service' => $shipment->service,

                        'tracking_number' => $shipment->tracking_number,

                        'tracking_url' => $shipment->tracking_url,

                        'shipped_at' => $shipment
                            ->shipped_at
                            ?->toISOString(),

                        'delivered_at' => $shipment
                            ->delivered_at
                            ?->toISOString(),

                        'returned_at' => $shipment
                            ->returned_at
                            ?->toISOString(),

                        'provider' => [
                            'code' => $providerCode,

                            'registered' => $providerRegistered,

                            'external' => $providerExternal,

                            'account' => $providerAccount instanceof ShippingProviderAccount
                                ? [
                                    'name' => $providerAccount->name,

                                    'auto_submit' => $providerAccount
                                        ->auto_submit,

                                    'operational' => $providerAccount
                                        ->isOperational(),
                                ]
                                : null,

                            'submission' => $submission instanceof ShipmentProviderSubmission
                                ? [
                                    'status' => [
                                        'value' => $submission
                                            ->status
                                            ->value,

                                        'label' => $submission
                                            ->status
                                            ->label(),
                                    ],

                                    'trigger' => $submissionTrigger,

                                    'last_trigger' => $lastSubmissionTrigger,

                                    'external_reference' => $submission
                                        ->external_reference,

                                    'attempt_count' => $submission
                                        ->attempt_count,

                                    'last_attempted_at' => $submission
                                        ->last_attempted_at
                                        ?->toISOString(),

                                    'submitted_at' => $submission
                                        ->submitted_at
                                        ?->toISOString(),

                                    'last_error' => $submission
                                        ->last_error,

                                    'tracking_sync_status' => [
                                        'value' => $submission
                                            ->tracking_sync_status
                                            ->value,

                                        'label' => $submission
                                            ->tracking_sync_status
                                            ->label(),
                                    ],

                                    'tracking_sync_attempt_count' => $submission
                                        ->tracking_sync_attempt_count,

                                    'provider_status' => $submission
                                        ->provider_status,

                                    'provider_mapped_shipment_status' => $submission
                                        ->provider_mapped_shipment_status
                                        !== null
                                        ? [
                                            'value' => $submission
                                                ->provider_mapped_shipment_status
                                                ->value,

                                            'label' => $submission
                                                ->provider_mapped_shipment_status
                                                ->label(),
                                        ]
                                        : null,

                                    'reconciliation_status' => [
                                        'value' => $submission
                                            ->reconciliation_status
                                            ->value,

                                        'label' => $submission
                                            ->reconciliation_status
                                            ->label(),
                                    ],

                                    'reconciliation_attempt_count' => $submission
                                        ->reconciliation_attempt_count,

                                    'last_tracking_sync_attempt_at' => $submission
                                        ->last_tracking_sync_attempt_at
                                        ?->toISOString(),

                                    'last_tracking_synced_at' => $submission
                                        ->last_tracking_synced_at
                                        ?->toISOString(),

                                    'last_tracking_sync_error' => $submission
                                        ->last_tracking_sync_error,

                                    'last_reconciliation_attempt_at' => $submission
                                        ->last_reconciliation_attempt_at
                                        ?->toISOString(),

                                    'last_reconciled_at' => $submission
                                        ->last_reconciled_at
                                        ?->toISOString(),

                                    'last_reconciliation_error' => $submission
                                        ->last_reconciliation_error,
                                ]
                                : null,
                        ],

                        'items' => $shipmentItems,

                        'actions' => $this
                            ->shipmentActions(
                                order: $order,

                                status: $shipment->status,

                                providerCode: $providerCode,

                                submission: $submission instanceof ShipmentProviderSubmission
                                ? $submission
                                : null,

                                carrier: $shipment->carrier,
                            ),
                    ];
                },
            )
            ->values()
            ->all();

        $createAllowed =
            $hasRemainingQuantity &&
            $shippingMethodOptions !== [] &&
            in_array(
                $order->status,
                [
                    OrderStatus::Pending,
                    OrderStatus::Confirmed,
                    OrderStatus::Processing,
                ],
                true,
            ) &&
            in_array(
                $order->fulfilment_status,
                [
                    FulfilmentStatus::Unfulfilled,
                    FulfilmentStatus::Processing,
                ],
                true,
            );

        $providerAccounts =
            ShippingProviderAccount::query()
                ->get([
                    'id',
                    'provider_code',
                    'name',
                    'is_active',
                    'is_default',
                    'connection_status',
                ])
                ->keyBy(
                    static fn (
                        ShippingProviderAccount $account,
                    ): string => $account
                        ->provider_code
                        ->value,
                );

        $providerOptions = [];

        foreach (
            ShippingProviderCode::cases() as $providerCode
        ) {
            if (
                $providerCode ===
                ShippingProviderCode::Manual
            ) {
                $providerOptions[] = [
                    'code' => $providerCode->value,

                    'label' => $providerCode->label(),

                    'external' => false,

                    'selectable' => true,

                    'connected' => true,

                    'is_default' => false,

                    'account_name' => null,
                ];

                continue;
            }

            $account =
                $providerAccounts->get(
                    $providerCode->value,
                );

            $providerOptions[] = [
                'code' => $providerCode->value,

                'label' => $providerCode->label(),

                'external' => true,

                'selectable' => $account instanceof ShippingProviderAccount
                    &&
                    $account->isOperational(),

                'connected' => $account instanceof ShippingProviderAccount
                    &&
                    $account->isConnected(),

                'is_default' => $account instanceof ShippingProviderAccount
                    &&
                    $account->is_default
                    &&
                    $account->isOperational(),

                'account_name' => $account?->name,
            ];
        }

        $defaultProviderCode =
            ShippingProviderCode::Manual
                ->value;

        foreach ($providerOptions as $providerOption) {
            if (
                $providerOption['selectable'] === true
                &&
                $providerOption['is_default'] === true
            ) {
                $defaultProviderCode =
                    (string) $providerOption['code'];

                break;
            }
        }

        return [
            'selected_method' => [
                'id' => $selectedShippingMethodId,

                'code' => $this->nullableStringAttribute(
                    $order,
                    'shipping_method_code',
                ),

                'name' => $this->nullableStringAttribute(
                    $order,
                    'shipping_method_name',
                ),
            ],

            'method_options' => $shippingMethodOptions,

            'provider_options' => $providerOptions,

            'default_provider_code' => $defaultProviderCode,

            'create_allowed' => $createAllowed,

            'summary' => [
                'shipment_count' => count(
                    $shipments,
                ),

                'total_ordered_quantity' => $totalOrderedQuantity,

                'total_active_allocated_quantity' => $totalAllocatedQuantity,

                'total_remaining_quantity' => $totalRemainingQuantity,
            ],

            'items' => $allocationItems,

            'shipments' => $shipments,
        ];
    }

    private function submissionTrigger(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            strtolower(
                trim(
                    $value,
                ),
            );

        return in_array(
            $value,
            [
                SubmitShipmentToProviderAction::TRIGGER_MANUAL,
                SubmitShipmentToProviderAction::TRIGGER_AUTOMATIC,
            ],
            true,
        )
            ? $value
            : null;
    }

    /**
     * @return array{
     *     can_start_processing: bool,
     *     can_update_tracking: bool,
     *     can_submit_provider: bool,
     *     can_sync_provider: bool,
     *     can_resolve_provider_review: bool,
     *     can_ship: bool,
     *     can_deliver: bool,
     *     can_cancel: bool,
     *     can_return: bool
     * }
     */
    private function shipmentActions(
        Order $order,
        ShipmentStatus $status,
        ?string $providerCode,
        ?ShipmentProviderSubmission $submission,
        ?string $carrier,
    ): array {
        $canStartProcessing =
            $status === ShipmentStatus::Pending &&
            (
                (
                    $order->status ===
                    OrderStatus::Confirmed &&
                    $order->fulfilment_status ===
                    FulfilmentStatus::Unfulfilled
                ) ||
                (
                    $order->status ===
                    OrderStatus::Processing &&
                    $order->fulfilment_status ===
                    FulfilmentStatus::Processing
                )
            );

        /*
         * Manual tracking fields are owned only by Manual
         * fulfilment. Legacy shipments without an explicit
         * provider remain editable for backward compatibility.
         *
         * External courier tracking must flow through provider
         * submission/tracking synchronization.
         */
        $manualTrackingOwned =
            $providerCode === null
            ||
            $providerCode ===
            ManualShippingProvider::CODE;

        $canUpdateTracking =
            $manualTrackingOwned
            &&
            in_array(
                $status,
                [
                    ShipmentStatus::Pending,
                    ShipmentStatus::Processing,
                    ShipmentStatus::Shipped,
                ],
                true,
            );

        $providerRegistered =
            is_string(
                $providerCode,
            ) &&
            $providerCode !== '' &&
            $this->providers->has(
                $providerCode,
            );

        $externalProvider =
            $providerRegistered &&
            $providerCode !==
            ManualShippingProvider::CODE;

        $canSubmitProvider =
            $externalProvider &&
            in_array(
                $status,
                [
                    ShipmentStatus::Pending,
                    ShipmentStatus::Processing,
                ],
                true,
            ) &&
            (
                $submission === null ||
                (
                    $submission->status ===
                    ShipmentProviderSubmissionStatus::Failed &&
                    $submission->provider_code ===
                    $providerCode
                )
            );

        $canSyncProvider =
            $submission instanceof ShipmentProviderSubmission &&
            $submission->status ===
            ShipmentProviderSubmissionStatus::Submitted &&
            $submission->provider_code !==
            ManualShippingProvider::CODE &&
            $this->providers->has(
                $submission->provider_code,
            ) &&
            ! in_array(
                $submission->tracking_sync_status,
                [
                    ShipmentProviderTrackingSyncStatus::Queued,
                    ShipmentProviderTrackingSyncStatus::Syncing,
                ],
                true,
            );

        $canResolveProviderReview =
            $submission instanceof ShipmentProviderSubmission &&
            $submission->status ===
            ShipmentProviderSubmissionStatus::ReviewRequired;

        $manualShipmentReadyToShip =
            ! $manualTrackingOwned
            ||
            (
                is_string(
                    $carrier,
                )
                &&
                trim(
                    $carrier,
                ) !== ''
            );

        $canShip =
            $status === ShipmentStatus::Processing &&
            $order->status ===
            OrderStatus::Processing &&
            $order->fulfilment_status ===
            FulfilmentStatus::Processing &&
            $manualShipmentReadyToShip;

        $canDeliver =
            $status === ShipmentStatus::Shipped &&
            $order->status ===
            OrderStatus::Processing &&
            in_array(
                $order->fulfilment_status,
                [
                    FulfilmentStatus::Processing,
                    FulfilmentStatus::Shipped,
                ],
                true,
            );

        $canCancel =
            $status->canTransitionTo(
                ShipmentStatus::Cancelled,
            );

        $canReturn =
            $status->canTransitionTo(
                ShipmentStatus::Returned,
            ) &&
            in_array(
                $order->status,
                [
                    OrderStatus::Processing,
                    OrderStatus::Completed,
                ],
                true,
            ) &&
            in_array(
                $order->fulfilment_status,
                [
                    FulfilmentStatus::Processing,
                    FulfilmentStatus::Shipped,
                    FulfilmentStatus::Delivered,
                ],
                true,
            );

        return [
            'can_start_processing' => $canStartProcessing,

            'can_update_tracking' => $canUpdateTracking,

            'can_submit_provider' => $canSubmitProvider,

            'can_sync_provider' => $canSyncProvider,

            'can_resolve_provider_review' => $canResolveProviderReview,

            'can_ship' => $canShip,

            'can_deliver' => $canDeliver,

            'can_cancel' => $canCancel,

            'can_return' => $canReturn,
        ];
    }

    private function nullableIntAttribute(
        Order $order,
        string $key,
    ): ?int {
        $value = $order->getAttribute(
            $key,
        );

        if (is_int($value)) {
            return $value;
        }

        if (
            is_string($value) &&
            ctype_digit($value)
        ) {
            return (int) $value;
        }

        return null;
    }

    private function nullableStringAttribute(
        Order $order,
        string $key,
    ): ?string {
        $value = $order->getAttribute(
            $key,
        );

        if (
            ! is_string($value) ||
            $value === ''
        ) {
            return null;
        }

        return $value;
    }
}
