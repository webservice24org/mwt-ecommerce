<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\ValidateCheckoutRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class CheckoutOrderController extends Controller
{
    public function __invoke(
        ValidateCheckoutRequest $request,
        CreateOrderAction $createOrder,
        CheckoutSessionService $checkoutSession,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $shippingRateId =
            $request->integer(
                'shipping_rate_id',
            );

        /*
         * The submission token remains server-owned.
         *
         * It protects checkout against accidental or
         * concurrent duplicate order submissions.
         */
        $idempotencyKey =
            $checkoutSession
                ->orderSubmissionToken();

        try {
            $order = $createOrder
                ->execute(
                    user: $user,

                    shippingRateId: $shippingRateId,

                    idempotencyKey: $idempotencyKey,
                );
        } catch (ValidationException $exception) {
            return redirect()
                ->route(
                    'checkout.index',
                )
                ->withErrors(
                    $exception->errors(),
                );
        }

        return redirect()
            ->route(
                'customer.orders.show',
                $order->id,
            )
            ->with(
                'success',
                'Your order has been placed successfully.',
            );
    }
}
