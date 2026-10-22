<?php

declare(strict_types=1);

namespace App\Domain\Mail\Data;

use App\Models\Order;

final readonly class OrderMailData
{
    /**
     * @param  list<array{
     *     product_name: string,
     *     variant_description: string|null,
     *     sku: string|null,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int
     * }>  $items
     */
    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public string $customerName,
        public string $customerEmail,
        public string $currencyCode,
        public int $subtotal,
        public int $discountTotal,
        public int $deliveryTotal,
        public int $taxTotal,
        public int $grandTotal,
        public string $status,
        public string $statusLabel,
        public string $paymentStatus,
        public string $paymentStatusLabel,
        public string $fulfilmentStatus,
        public string $fulfilmentStatusLabel,
        public array $items,
        public ?string $invoiceUrl,
    ) {}

    public static function fromOrder(
        Order $order,
        ?string $invoiceUrl = null,
    ): self {
        $order->loadMissing([
            'user',
            'items',
        ]);

        return new self(
            orderId: $order->id,

            orderNumber: $order->order_number,

            customerName: $order->user->name,

            customerEmail: $order->user->email,

            currencyCode: $order->currency_code,

            subtotal: $order->subtotal,

            discountTotal: $order->discount_total,

            deliveryTotal: $order->delivery_total,

            taxTotal: $order->tax_total,

            grandTotal: $order->grand_total,

            status: $order->status->value,

            statusLabel: $order->status->label(),

            paymentStatus: $order->payment_status->value,

            paymentStatusLabel: $order->payment_status->label(),

            fulfilmentStatus: $order->fulfilment_status->value,

            fulfilmentStatusLabel: $order->fulfilment_status->label(),

            items: $order->items
                ->map(
                    static fn ($item): array => [
                        'product_name' => $item->product_name,

                        'variant_description' => $item->variant_description,

                        'sku' => $item->sku,

                        'quantity' => $item->quantity,

                        'unit_price' => $item->unit_price,

                        'line_total' => $item->line_total,
                    ],
                )
                ->values()
                ->all(),

            invoiceUrl: $invoiceUrl,
        );
    }
}
