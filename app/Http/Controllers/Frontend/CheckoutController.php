<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Cart\Services\CartPromoPricingService;
use App\Domain\Cart\Services\CartService;
use App\Domain\Checkout\Actions\CalculateCheckoutTotalsAction;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        CartService $cart,
        CheckoutSessionService $checkoutSession,
        CalculateCheckoutTotalsAction $calculateTotals,
        CartPromoPricingService $promoPricing,
    ): Response|RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        /*
         * ==================================================
         * CART VALIDATION
         * ==================================================
         */
        $validation =
            $cart->validateForCheckout();

        if (
            $validation->wasSanitized
        ) {
            return redirect()
                ->route(
                    'cart.show',
                )
                ->with(
                    'error',
                    'Your cart changed because one or more items are no longer available. Please review your cart before checkout.',
                );
        }

        if (
            $validation
                ->summary
                ->items === []
        ) {
            return redirect()
                ->route(
                    'cart.show',
                )
                ->with(
                    'error',
                    'Your cart is empty.',
                );
        }

        /*
         * ==================================================
         * DELIVERY ADDRESS
         * ==================================================
         *
         * Address is still required for delivery/order
         * creation, but it does NOT automatically decide
         * the shipping zone in manual-zone mode.
         */
        $selectedAddress =
            $checkoutSession
                ->resolveAddress(
                    $user,
                );

        $addresses = $user
            ->addresses()
            ->select([
                'id',
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
                'created_at',
            ])
            ->orderByDesc(
                'is_default',
            )
            ->orderByDesc(
                'id',
            )
            ->get();

        /*
         * ==================================================
         * AUTHORITATIVE PROMO-AWARE CART PRICING
         * ==================================================
         *
         * Promo selection lives in server-side cart state.
         * Recalculate it for the authenticated customer every
         * time checkout is rendered. Invalid/stale promos are
         * cleared by CartPromoPricingService.
         */
        $pricing =
            $promoPricing->calculate(
                cart: $validation
                    ->summary,

                userId: $user->id,
            );

        /*
         * ==================================================
         * BASE CHECKOUT TOTALS
         * ==================================================
         *
         * No browser-supplied delivery charge is trusted.
         *
         * React previews the selected zone rate. Laravel will
         * calculate both promo discount and delivery again
         * during final checkout validation.
         */
        $totals =
            $calculateTotals->execute(
                pricing: $pricing,

                deliveryTotal: 0,
            );

        /*
         * Shipping eligibility continues to use the original
         * authoritative cart subtotal rather than a browser
         * total or client-calculated discounted amount.
         */
        $subtotal =
            $pricing
                ->cart
                ->subtotal;

        /*
         * ==================================================
         * MANUAL SHIPPING ZONES
         * ==================================================
         *
         * Only active zones are exposed.
         *
         * Matching Rules are deliberately NOT required.
         */
        $zones = ShippingZone::query()
            ->where(
                'is_active',
                true,
            )
            ->with([
                'rates.shippingMethod',
            ])
            ->orderBy(
                'priority',
            )
            ->orderBy(
                'id',
            )
            ->get();

        $shippingZones =
            $zones
                ->map(
                    static function (ShippingZone $zone) use ($subtotal): ?array {
                        /*
                         * Find rates that are really usable
                         * for the current cart.
                         */
                        $eligibleRates =
                            $zone
                                ->rates
                                ->filter(
                                    static function (ShippingRate $rate) use ($subtotal): bool {
                                        if (
                                            ! $rate
                                                ->is_active
                                        ) {
                                            return false;
                                        }

                                        $method =
                                            $rate
                                                ->shippingMethod;

                                        if (
                                            $method ===
                                            null ||
                                            ! $method
                                                ->is_active
                                        ) {
                                            return false;
                                        }

                                        if (
                                            $rate
                                                ->min_order_amount !==
                                            null &&
                                            $subtotal <
                                            $rate
                                                ->min_order_amount
                                        ) {
                                            return false;
                                        }

                                        if (
                                            $rate
                                                ->max_order_amount !==
                                            null &&
                                            $subtotal >
                                            $rate
                                                ->max_order_amount
                                        ) {
                                            return false;
                                        }

                                        return true;
                                    },
                                )
                                ->sort(
                                    static function (ShippingRate $left, ShippingRate $right): int {
                                        /*
                                         * Lowest numeric priority
                                         * wins.
                                         */
                                        $priorityComparison =
                                            $left
                                                ->priority
                                            <=>
                                            $right
                                                ->priority;

                                        if (
                                            $priorityComparison !==
                                            0
                                        ) {
                                            return $priorityComparison;
                                        }

                                        /*
                                         * Deterministic fallback.
                                         */
                                        return $left
                                            ->id
                                            <=>
                                            $right
                                                ->id;
                                    },
                                );

                        $rate =
                            $eligibleRates
                                ->first();

                        /*
                         * A zone without an eligible rate
                         * must not be shown at checkout.
                         */
                        if (
                            ! $rate instanceof ShippingRate
                        ) {
                            return null;
                        }

                        $method =
                            $rate
                                ->shippingMethod;

                        if ($method === null) {
                            return null;
                        }

                        /*
                         * Free-shipping threshold remains
                         * server-owned.
                         */
                        $free =
                            $rate
                                ->free_shipping_threshold !==
                            null &&
                            $subtotal >=
                            $rate
                                ->free_shipping_threshold;

                        $amount =
                            $free
                            ? 0
                            : (int) $rate
                                ->amount;

                        return [
                            'zone_id' => (int) $zone
                                ->id,

                            'zone_name' => $zone
                                ->name,

                            'zone_code' => $zone
                                ->code,

                            'shipping_rate_id' => (int) $rate
                                ->id,

                            'rate_name' => $rate
                                ->name,

                            'rate_code' => $rate
                                ->code,

                            'method_name' => $method
                                ->name,

                            'amount' => $amount,

                            'free' => $free,
                        ];
                    },
                )
                ->filter()
                ->values()
                ->all();

        /*
         * ==================================================
         * PAYMENT
         * ==================================================
         */
        $paymentMethod =
            $checkoutSession
                ->paymentMethod();

        return Inertia::render(
            'Frontend/Checkout/Index',
            [
                'cart' => $pricing
                    ->cart
                    ->toArray(),

                'promo' => $pricing
                    ->promo
                    ?->toArray(),

                'totals' => $totals
                    ->toArray(),

                /*
                 * Global/unzoned rates are deliberately
                 * NOT exposed here.
                 */
                'shippingZones' => $shippingZones,

                'addresses' => $addresses,

                'selectedAddressId' => $selectedAddress
                    ?->id,

                'addressDefaults' => [
                    'country_code' => 'BD',
                ],

                'payment' => [
                    'method' => $paymentMethod
                        ->value,

                    'label' => $paymentMethod
                        ->label(),

                    'description' => $paymentMethod
                        ->description(),
                ],
            ],
        );
    }
}
