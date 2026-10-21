<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Orders\Actions\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CheckoutOrderController extends Controller
{
    public function __invoke(
        Request $request,
        CreateOrderAction $createOrder,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $order = $createOrder->execute(
            $user,
        );

        return redirect()
            ->route(
                'customer.orders.show',
                [
                    'order' => $order->id,
                ],
            )
            ->with(
                'success',
                'Your order has been placed successfully.',
            );
    }
}
