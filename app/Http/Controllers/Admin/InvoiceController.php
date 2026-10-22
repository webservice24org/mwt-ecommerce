<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Queries\GetInvoiceBrandingQuery;
use App\Domain\Orders\Queries\GetInvoiceDataQuery;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Inertia\Inertia;
use Inertia\Response;

final class InvoiceController extends Controller
{
    public function show(
        Invoice $invoice,
        GetInvoiceDataQuery $invoiceData,
        GetInvoiceBrandingQuery $branding,
    ): Response {
        return Inertia::render(
            'Admin/Invoices/Show',
            [
                'invoice' => $invoiceData
                    ->execute(
                        $invoice,
                    )
                    ->toArray(),

                'branding' => $branding->execute(),

                'back_url' => route(
                    'admin.orders.show',
                    $invoice->order_id,
                ),
            ],
        );
    }
}
