<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Cart\Services\CartService;
use App\Domain\Checkout\Actions\CalculateCheckoutTotalsAction;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Http\Controllers\Controller;
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
    ): Response|RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $validation = $cart
            ->validateForCheckout();

        if ($validation->wasSanitized) {
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
         * Address is intentionally optional on GET /checkout.
         *
         * This allows a customer with no saved addresses to
         * reach checkout and create one there.
         *
         * The strict address requirement is enforced by
         * ValidateCheckoutAction when checkout.validate runs.
         */
        $selectedAddress = $checkoutSession
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

        $totals = $calculateTotals
            ->execute(
                $validation->summary,
            );

        $paymentMethod = $checkoutSession
            ->paymentMethod();

        return Inertia::render(
            'Frontend/Checkout/Index',
            [
                'cart' => $validation
                    ->summary
                    ->toArray(),

                'totals' => $totals
                    ->toArray(),

                'addresses' => $addresses,

                'selectedAddressId' => $selectedAddress?->id,

                'addressDefaults' => [
                    'country_code' => 'BD',
                ],

                'payment' => [
                    'method' => $paymentMethod->value,

                    'label' => $paymentMethod->label(),

                    'description' => $paymentMethod
                        ->description(),
                ],
            ],
        );
    }
}
