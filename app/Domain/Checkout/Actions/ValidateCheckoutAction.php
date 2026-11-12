<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

use App\Domain\Cart\Services\CartPromoPricingService;
use App\Domain\Cart\Services\CartService;
use App\Domain\Checkout\Data\CheckoutValidationData;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Shipping\Actions\ResolveSelectedShippingRateAction;
use App\Domain\Shipping\Data\ShippingDestinationData;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ValidateCheckoutAction
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutSessionService $checkoutSession,
        private readonly CalculateCheckoutTotalsAction $calculateTotals,
        private readonly ResolveSelectedShippingRateAction $resolveShippingRate,
        private readonly CartPromoPricingService $promoPricing,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        User $user,
        int $shippingRateId,
    ): CheckoutValidationData {
        /*
         * ==================================================
         * AUTHORITATIVE CART VALIDATION
         * ==================================================
         */
        $cartValidation =
            $this->cart
                ->validateForCheckout();

        if ($cartValidation->wasSanitized) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart changed because one or more items are no longer available. Please review your cart.',
            ]);
        }

        if (
            $cartValidation
                ->summary
                ->items === []
        ) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        /*
         * ==================================================
         * FINAL PROMO REVALIDATION
         * ==================================================
         *
         * This happens again immediately before order
         * creation.
         *
         * CartPromoPricingService may clear a stale promo
         * when it is no longer eligible.
         */
        $pricing =
            $this->promoPricing
                ->calculate(
                    cart: $cartValidation
                        ->summary,

                    userId: $user->id,
                );

        /*
         * ==================================================
         * DELIVERY ADDRESS
         * ==================================================
         */
        $address =
            $this->checkoutSession
                ->resolveAddress(
                    $user,
                );

        if ($address === null) {
            throw ValidationException::withMessages([
                'address_id' => 'Please select a delivery address.',
            ]);
        }

        /*
         * Re-resolve through the authenticated customer's
         * relationship so checkout cannot use an address
         * belonging to another customer.
         */
        $ownedAddress =
            $user
                ->addresses()
                ->whereKey(
                    $address->id,
                )
                ->first();

        if ($ownedAddress === null) {
            $this
                ->checkoutSession
                ->forgetAddress();

            throw ValidationException::withMessages([
                'address_id' => 'The selected delivery address is no longer available.',
            ]);
        }

        $paymentMethod =
            $this
                ->checkoutSession
                ->paymentMethod();

        /*
         * ==================================================
         * SHIPPING DESTINATION
         * ==================================================
         *
         * Shipping eligibility is based on the cart
         * subtotal before promotional discount.
         *
         * This preserves the existing shipping-rate
         * boundary semantics.
         */
        $destination =
            new ShippingDestinationData(
                countryCode: $ownedAddress
                    ->country_code,

                stateOrRegion: $ownedAddress
                    ->state_or_region,

                city: $ownedAddress
                    ->city,

                postalCode: $ownedAddress
                    ->postal_code,
            );

        $orderSubtotal =
            $cartValidation
                ->summary
                ->subtotal;

        /*
         * ==================================================
         * FINAL SHIPPING-RATE REVALIDATION
         * ==================================================
         */
        $shippingQuote =
            $this
                ->resolveShippingRate
                ->execute(
                    shippingRateId: $shippingRateId,

                    orderSubtotal: $orderSubtotal,

                    destination: $destination,
                );

        /*
         * ==================================================
         * AUTHORITATIVE CHECKOUT TOTALS
         * ==================================================
         *
         * pricing contains:
         *
         * subtotal
         * promo
         * discount_total
         * subtotal-after-discount
         *
         * Shipping is then added by checkout.
         */
        $totals =
            $this
                ->calculateTotals
                ->execute(
                    pricing: $pricing,

                    deliveryTotal: $shippingQuote
                        ->amount,
                );

        return new CheckoutValidationData(
            cart: $cartValidation
                ->summary,

            totals: $totals,

            address: $ownedAddress,

            paymentMethod: $paymentMethod,

            shippingQuote: $shippingQuote,

            promo: $pricing->promo,
        );
    }
}
