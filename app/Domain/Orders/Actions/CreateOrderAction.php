<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Cart\Services\CartService;
use App\Domain\Checkout\Actions\ValidateCheckoutAction;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Services\OrderNumberGenerator;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class CreateOrderAction
{
    public function __construct(
        private ValidateCheckoutAction $validateCheckout,
        private BuildOrderItemSnapshotsAction $buildItemSnapshots,
        private ValidateAndDecrementOrderStockAction $stock,
        private OrderNumberGenerator $orderNumberGenerator,
        private CartService $cart,
        private CheckoutSessionService $checkoutSession,
    ) {}

    public function execute(
        User $user,
        ?string $idempotencyKey = null,
    ): Order {
        /*
         * Existing callers may omit the key.
         *
         * The submission identity remains server-owned.
         */
        $idempotencyKey ??= $this
            ->checkoutSession
            ->orderSubmissionToken();

        /*
         * ==================================================
         * IDEMPOTENCY FAST PATH
         * ==================================================
         *
         * This must happen before checkout validation.
         *
         * A successful first submission clears the cart, so
         * a repeated request with the same logical submission
         * key must return the already-created order.
         */
        $existingOrder = $this
            ->findExistingOrder(
                $user,
                $idempotencyKey,
            );

        if ($existingOrder !== null) {
            return $existingOrder;
        }

        /*
         * ==================================================
         * AUTHORITATIVE CHECKOUT VALIDATION
         * ==================================================
         */
        $checkout = $this
            ->validateCheckout
            ->execute(
                $user,
            );

        /*
         * Build immutable item snapshots before persistence.
         */
        $itemSnapshots = $this
            ->buildItemSnapshots
            ->execute(
                $checkout->cart,
            );

        try {
            /*
             * ==================================================
             * ATOMIC ORDER + STOCK + INITIAL HISTORY
             * ==================================================
             *
             * All of the following belong to one transaction:
             *
             * - stock validation/decrement
             * - order persistence
             * - order-item snapshots
             * - initial order status history
             * - initial payment status history
             * - initial fulfilment status history
             *
             * A failure anywhere rolls everything back.
             */
            $order = DB::transaction(
                function () use ($user, $checkout, $itemSnapshots, $idempotencyKey): Order {
                    $this->stock->execute(
                        $checkout->cart,
                    );

                    $address =
                        $checkout->address;

                    $totals =
                        $checkout->totals;

                    $order = Order::query()
                        ->create([
                            'user_id' => $user->id,

                            'idempotency_key' => $idempotencyKey,

                            'delivery_address_id' => $address->id,

                            'order_number' => $this
                                ->orderNumberGenerator
                                ->generate(),

                            'status' => OrderStatus::Pending,

                            'payment_method' => $checkout
                                ->paymentMethod,

                            'payment_status' => PaymentStatus::Unpaid,

                            'fulfilment_status' => FulfilmentStatus::Unfulfilled,

                            'currency_code' => 'BDT',

                            'subtotal' => $totals->subtotal,

                            'discount_total' => $totals
                                ->discountTotal,

                            'delivery_total' => $totals
                                ->deliveryTotal,

                            'tax_total' => $totals
                                ->taxTotal,

                            'grand_total' => $totals
                                ->grandTotal,

                            /*
                             * Immutable delivery-address
                             * snapshot.
                             */
                            'delivery_recipient_name' => $address
                                ->recipient_name,

                            'delivery_phone' => $address->phone,

                            'delivery_address_line_1' => $address
                                ->address_line_1,

                            'delivery_address_line_2' => $address
                                ->address_line_2,

                            'delivery_city' => $address->city,

                            'delivery_state_or_region' => $address
                                ->state_or_region,

                            'delivery_postal_code' => $address
                                ->postal_code,

                            'delivery_country_code' => $address
                                ->country_code,

                            'placed_at' => now(),
                        ]);

                    foreach (
                        $itemSnapshots as $snapshot
                    ) {
                        $order
                            ->items()
                            ->create(
                                $snapshot
                                    ->toArray(),
                            );
                    }

                    /*
                     * ==================================================
                     * INITIAL STATUS HISTORY
                     * ==================================================
                     *
                     * These are creation events, therefore there is
                     * no previous status.
                     */
                    $changedAt =
                        now();

                    $order
                        ->statusHistories()
                        ->create([
                            'type' => OrderStatusHistory::TYPE_ORDER,

                            'from_status' => null,

                            'to_status' => OrderStatus::Pending
                                ->value,

                            'actor_type' => $user->getMorphClass(),

                            'actor_id' => $user->getKey(),

                            'note' => 'Order placed.',

                            'changed_at' => $changedAt,
                        ]);

                    $order
                        ->statusHistories()
                        ->create([
                            'type' => OrderStatusHistory::TYPE_PAYMENT,

                            'from_status' => null,

                            'to_status' => PaymentStatus::Unpaid
                                ->value,

                            'actor_type' => $user->getMorphClass(),

                            'actor_id' => $user->getKey(),

                            'note' => 'Payment awaiting collection.',

                            'changed_at' => $changedAt,
                        ]);

                    $order
                        ->statusHistories()
                        ->create([
                            'type' => OrderStatusHistory::TYPE_FULFILMENT,

                            'from_status' => null,

                            'to_status' => FulfilmentStatus::Unfulfilled
                                ->value,

                            'actor_type' => $user->getMorphClass(),

                            'actor_id' => $user->getKey(),

                            'note' => 'Order awaiting fulfilment.',

                            'changed_at' => $changedAt,
                        ]);

                    return $order->load([
                        'items',
                        'statusHistories',
                    ]);
                },
            );
        } catch (QueryException $exception) {
            /*
             * ==================================================
             * CONCURRENT DUPLICATE SUBMISSION
             * ==================================================
             *
             * If another request won the unique idempotency-key
             * race, this transaction—including any initial
             * history rows—has rolled back.
             */
            $existingOrder = $this
                ->findExistingOrder(
                    $user,
                    $idempotencyKey,
                );

            if ($existingOrder !== null) {
                return $existingOrder;
            }

            throw $exception;
        }

        /*
         * Only the request that actually created the order
         * reaches this point.
         */
        $this->cart->clear();

        $this
            ->checkoutSession
            ->rotateOrderSubmissionToken();

        return $order;
    }

    private function findExistingOrder(
        User $user,
        string $idempotencyKey,
    ): ?Order {
        return Order::query()
            ->with([
                'items',
                'statusHistories',
            ])
            ->where(
                'user_id',
                $user->id,
            )
            ->where(
                'idempotency_key',
                $idempotencyKey,
            )
            ->first();
    }
}
