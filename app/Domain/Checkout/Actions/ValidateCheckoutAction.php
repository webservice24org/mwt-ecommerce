<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

use App\Domain\Cart\Services\CartService;
use App\Domain\Checkout\Data\CheckoutValidationData;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ValidateCheckoutAction
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutSessionService $checkoutSession,
        private readonly CalculateCheckoutTotalsAction $calculateTotals,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        User $user,
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

        $totals =
            $this->calculateTotals
                ->execute(
                    $cartValidation
                        ->summary,
                );

        return new CheckoutValidationData(
            cart: $cartValidation->summary,
            totals: $totals,
            address: $ownedAddress,
            paymentMethod: $paymentMethod,
        );
    }
}
