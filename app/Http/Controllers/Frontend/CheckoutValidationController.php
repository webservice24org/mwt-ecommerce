<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Checkout\Actions\ValidateCheckoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\ValidateCheckoutRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class CheckoutValidationController extends Controller
{
    public function __invoke(
        ValidateCheckoutRequest $request,
        ValidateCheckoutAction $action,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        try {
            $action->execute(
                user: $user,

                shippingRateId: $request->integer(
                    'shipping_rate_id',
                ),
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
                'checkout.index',
            )
            ->with(
                'success',
                'Checkout details validated successfully.',
            );
    }
}
