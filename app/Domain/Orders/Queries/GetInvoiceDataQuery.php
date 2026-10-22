<?php

declare(strict_types=1);

namespace App\Domain\Orders\Queries;

use App\Domain\Orders\Data\InvoiceAddressData;
use App\Domain\Orders\Data\InvoiceData;
use App\Domain\Orders\Data\InvoiceItemData;
use App\Models\Invoice;
use App\Models\OrderItem;

final class GetInvoiceDataQuery
{
    public function execute(
        Invoice $invoice,
    ): InvoiceData {
        $invoice->loadMissing([
            'order.items',
        ]);

        $order =
            $invoice->order;

        $items = $order
            ->items
            ->sortBy(
                'id',
            )
            ->values()
            ->map(
                static fn (
                    OrderItem $item,
                ): InvoiceItemData => new InvoiceItemData(
                    productName: $item->product_name,

                    sku: $item->sku,

                    variantDescription: $item->variant_description,

                    unitPrice: $item->unit_price,

                    quantity: $item->quantity,

                    lineTotal: $item->line_total,
                ),
            )
            ->all();

        return new InvoiceData(
            id: $invoice->id,

            invoiceNumber: $invoice->invoice_number,

            orderNumber: $order->order_number,

            /*
             * Invoice-owned customer snapshot.
             */
            customerName: $invoice->customer_name,

            customerEmail: $invoice->customer_email,

            /*
             * All commercial values below come from
             * the immutable order snapshot.
             */
            currencyCode: $order->currency_code,

            subtotal: $order->subtotal,

            discountTotal: $order->discount_total,

            deliveryTotal: $order->delivery_total,

            taxTotal: $order->tax_total,

            grandTotal: $order->grand_total,

            deliveryAddress: new InvoiceAddressData(
                recipientName: $order->delivery_recipient_name,

                phone: $order->delivery_phone,

                addressLine1: $order->delivery_address_line_1,

                addressLine2: $order->delivery_address_line_2,

                city: $order->delivery_city,

                stateOrRegion: $order->delivery_state_or_region,

                postalCode: $order->delivery_postal_code,

                countryCode: $order->delivery_country_code,
            ),

            items: $items,

            issuedAt: $invoice
                ->issued_at
                ->toISOString(),
        );
    }
}
