<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Domain\Orders\Queries\GetInvoiceBrandingQuery;
use App\Domain\Orders\Queries\GetInvoiceDataQuery;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InvoiceController extends Controller
{
    public function show(
        Request $request,
        int $invoice,
        GetInvoiceDataQuery $invoiceData,
        GetInvoiceBrandingQuery $branding,
    ): Response {
        $user =
            $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        /*
         * Ownership is part of the SQL query.
         *
         * Another customer's invoice therefore
         * resolves as 404 instead of leaking its
         * existence.
         */
        $customerInvoice =
            Invoice::query()
                ->whereKey(
                    $invoice,
                )
                ->whereHas(
                    'order',
                    static fn ($query) => $query->where(
                        'user_id',
                        $user->id,
                    ),
                )
                ->firstOrFail();

        return Inertia::render(
            'Customer/Invoices/Show',
            [
                'invoice' => $invoiceData
                    ->execute(
                        $customerInvoice,
                    )
                    ->toArray(),

                'branding' => $branding->execute(),

                'back_url' => route(
                    'customer.orders.show',
                    $customerInvoice->order_id,
                ),
            ],
        );
    }
}
