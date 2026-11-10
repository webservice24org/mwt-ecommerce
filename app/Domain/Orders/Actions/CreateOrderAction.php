<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Cart\Services\CartService;
use App\Domain\Checkout\Actions\ValidateCheckoutAction;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Mail\Actions\SendOrderPlacedNotificationsAction;
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
        private SendOrderPlacedNotificationsAction $sendOrderPlacedNotifications,
    ) {}

    public function execute(
        User $user,
        int $shippingRateId,
        ?string $idempotencyKey = null,
    ): Order {
        $idempotencyKey ??= $this
            ->checkoutSession
            ->orderSubmissionToken();

        /*
         * ==================================================
         * IDEMPOTENCY FAST PATH
         * ==================================================
         *
         * Must remain before checkout validation because a
         * successful first request clears the cart.
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
         *
         * The shipping rate ID comes from the client, but
         * availability and price are resolved again by the
         * server.
         */
        $checkout = $this
            ->validateCheckout
            ->execute(
                user: $user,
                shippingRateId: $shippingRateId,
            );

        $itemSnapshots = $this
            ->buildItemSnapshots
            ->execute(
                $checkout->cart,
            );

        try {
            $order = DB::transaction(
                function () use ($user, $checkout, $itemSnapshots, $idempotencyKey): Order {
                    $this->stock->execute(
                        $checkout->cart,
                    );

                    $address =
                        $checkout->address;

                    $totals =
                        $checkout->totals;

                    $shippingQuote =
                        $checkout->shippingQuote;

                    $order = Order::query()
                        ->create([
                            'user_id' => $user->id,

                            'idempotency_key' => $idempotencyKey,

                            'delivery_address_id' => $address->id,

                            /*
                             * Current shipping references.
                             */
                            'shipping_method_id' => $shippingQuote
                                ->shippingMethodId,

                            'shipping_rate_id' => $shippingQuote
                                ->shippingRateId,

                            /*
                             * Immutable shipping snapshots.
                             *
                             * Historical orders must not
                             * change if an administrator later
                             * renames a method or rate.
                             */
                            'shipping_method_code' => $shippingQuote
                                ->methodCode,

                            'shipping_method_name' => $shippingQuote
                                ->methodName,

                            'shipping_rate_code' => $shippingQuote
                                ->rateCode,

                            'shipping_rate_name' => $shippingQuote
                                ->rateName,

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

                            /*
                             * Server-calculated shipping
                             * amount only.
                             */
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
                     */
                    $changedAt = now();

                    $order
                        ->statusHistories()
                        ->create([
                            'type' => OrderStatusHistory::TYPE_ORDER,

                            'from_status' => null,

                            'to_status' => OrderStatus::Pending
                                ->value,

                            'actor_type' => $user
                                ->getMorphClass(),

                            'actor_id' => $user
                                ->getKey(),

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

                            'actor_type' => $user
                                ->getMorphClass(),

                            'actor_id' => $user
                                ->getKey(),

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

                            'actor_type' => $user
                                ->getMorphClass(),

                            'actor_id' => $user
                                ->getKey(),

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
         * Only the request that actually created
         * the order reaches this point.
         */
        $this->cart->clear();

        $this
            ->checkoutSession
            ->rotateOrderSubmissionToken();

        /*
         * Notification dispatch remains post-commit.
         */
        $this
            ->sendOrderPlacedNotifications
            ->execute(
                $order,
            );

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
