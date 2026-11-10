<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Actions\MarkOrderPaidAction;
use App\Domain\Orders\Actions\MarkOrderPaymentDueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\MarkOrderPaidRequest;
use App\Http\Requests\Admin\Orders\MarkOrderPaymentDueRequest;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

final class OrderPaymentController extends Controller
{
    public function due(
        MarkOrderPaymentDueRequest $request,
        Order $order,
        MarkOrderPaymentDueAction $action,
    ): RedirectResponse {
        $action->execute(
            order: $order,

            paymentDueAt: $request
                ->paymentDueAt(),

            actor: $this->admin(
                $request,
            ),

            note: $request
                ->note(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Payment marked as due.',
        );
    }

    public function paid(
        MarkOrderPaidRequest $request,
        Order $order,
        MarkOrderPaidAction $action,
    ): RedirectResponse {
        $action->execute(
            order: $order,

            actor: $this->admin(
                $request,
            ),

            note: $request
                ->note(),
        );

        return to_route(
            'admin.orders.show',
            $order,
        )->with(
            'success',
            'Payment marked as paid.',
        );
    }

    private function admin(
        MarkOrderPaymentDueRequest|
        MarkOrderPaidRequest $request,
    ): Admin {
        $admin =
            $request->user(
                'admin',
            );

        abort_unless(
            $admin instanceof Admin,
            403,
        );

        return $admin;
    }
}
