<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Checkout\Actions\ValidateCheckoutAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CheckoutValidationController extends Controller
{
    public function __invoke(
        Request $request,
        ValidateCheckoutAction $action,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        try {
            $action->execute(
                $user,
            );
        } catch (
            ValidationException $exception
        ) {
            return redirect()
                ->route(
                    'checkout.index',
                )
                ->withErrors(
                    $exception->errors(),
                );
        }

        /*
         * Phase 6.3E stops here.
         *
         * Order creation begins in Phase 6.4.
         */
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
