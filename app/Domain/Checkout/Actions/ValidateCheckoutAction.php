<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

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
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        User $user,
        int $shippingRateId,
    ): CheckoutValidationData {
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
         * relationship so checkout can never continue with
         * an address belonging to another customer.
         */
        $ownedAddress = $user
            ->addresses()
            ->whereKey(
                $address->id,
            )
            ->first();

        if ($ownedAddress === null) {
            $this->checkoutSession
                ->forgetAddress();

            throw ValidationException::withMessages([
                'address_id' => 'The selected delivery address is no longer available.',
            ]);
        }

        $paymentMethod =
            $this->checkoutSession
                ->paymentMethod();

        /*
         * Build the shipping destination exclusively from
         * the server-owned customer address. Never trust
         * independently posted address values when deciding
         * which shipping rates are eligible.
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

        /*
         * The cart currently exposes its subtotal as a float,
         * while authoritative checkout/order money uses
         * integer values throughout the application.
         */
        $orderSubtotal = (int) round(
            $cartValidation
                ->summary
                ->subtotal,
        );

        /*
         * Re-resolve the selected shipping rate on the
         * server against:
         *
         * - active shipping method
         * - active rate
         * - subtotal boundaries
         * - destination / shipping zone
         * - free-shipping threshold
         *
         * The client supplies only the rate ID.
         */
        $shippingQuote =
            $this->resolveShippingRate
                ->execute(
                    shippingRateId: $shippingRateId,

                    orderSubtotal: $orderSubtotal,

                    destination: $destination,
                );

        /*
         * deliveryTotal comes exclusively from the
         * authoritative shipping quote. A client-provided
         * delivery amount is never used here.
         */
        $totals =
            $this->calculateTotals
                ->execute(
                    cart: $cartValidation
                        ->summary,

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
        );
    }
}
