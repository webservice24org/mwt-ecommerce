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
         * CheckoutSessionService creates and stores the
         * token in the checkout session.
         *
         * Tests that specifically exercise idempotency may
         * still pass an explicit key.
         */
        $idempotencyKey ??= $this
            ->checkoutSession
            ->orderSubmissionToken();

        /*
         * ==================================================
         * IDEMPOTENCY FAST PATH
         * ==================================================
         *
         * This lookup MUST happen before checkout
         * validation.
         *
         * A successful first submission clears the cart.
         * Therefore a repeated request using the same key
         * must return the existing order rather than
         * validating the now-empty cart.
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
         * Revalidate immediately before order creation.
         *
         * The browser is never authoritative for:
         *
         * - prices
         * - totals
         * - payment method
         * - delivery address
         * - item details
         */
        $checkout = $this
            ->validateCheckout
            ->execute(
                $user,
            );

        /*
         * Build immutable order-item snapshots before
         * persistence.
         *
         * No database writes happen here.
         */
        $itemSnapshots = $this
            ->buildItemSnapshots
            ->execute(
                $checkout->cart,
            );

        try {
            /*
             * ==================================================
             * ATOMIC ORDER + STOCK TRANSACTION
             * ==================================================
             *
             * Stock validation/decrement, order creation and
             * order-item persistence are one transaction.
             *
             * If any operation fails, every write in this
             * transaction is rolled back.
             */
            $order = DB::transaction(
                function () use ($user, $checkout, $itemSnapshots, $idempotencyKey): Order {
                    /*
                     * Lock the relevant stock rows, revalidate
                     * current availability and decrement the
                     * inventory atomically.
                     */
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

                            /*
                             * Server-owned submission identity.
                             *
                             * The database unique constraint on
                             *
                             * user_id + idempotency_key
                             *
                             * provides the final duplicate-
                             * submission race protection.
                             */
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

                    return $order->load(
                        'items',
                    );
                },
            );
        } catch (QueryException $exception) {
            /*
             * ==================================================
             * CONCURRENT DUPLICATE SUBMISSION
             * ==================================================
             *
             * Two requests may both pass the fast-path lookup
             * before either request commits.
             *
             * The unique database constraint is therefore the
             * final concurrency authority.
             *
             * If this request lost that race, the transaction
             * above has been rolled back, including its stock
             * changes. Return the order created by the winning
             * request.
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
             * This was some other database error.
             */
            throw $exception;
        }

        /*
         * ==================================================
         * POST-COMMIT CLEANUP
         * ==================================================
         *
         * Only the request that actually created the order
         * reaches this point.
         *
         * Never clear the cart before the transaction has
         * successfully committed.
         */
        $this->cart->clear();

        /*
         * Prepare a fresh token for a future checkout.
         *
         * Requests already in flight with the previous token
         * remain protected by the persisted idempotency key.
         */
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
            ->with(
                'items',
            )
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
