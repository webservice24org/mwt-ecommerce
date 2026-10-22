<?php

declare(strict_types=1);

namespace App\Domain\Orders\Data;

final readonly class InvoiceData
{
    /**
     * @param  list<InvoiceItemData>  $items
     */
    public function __construct(
        public int $id,
        public string $invoiceNumber,
        public string $orderNumber,
        public string $customerName,
        public string $customerEmail,
        public string $currencyCode,
        public int $subtotal,
        public int $discountTotal,
        public int $deliveryTotal,
        public int $taxTotal,
        public int $grandTotal,
        public InvoiceAddressData $deliveryAddress,
        public array $items,
        public string $issuedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     invoice_number: string,
     *     order_number: string,
     *     customer: array{
     *         name: string,
     *         email: string
     *     },
     *     currency_code: string,
     *     subtotal: int,
     *     discount_total: int,
     *     delivery_total: int,
     *     tax_total: int,
     *     grand_total: int,
     *     delivery_address: array{
     *         recipient_name: string,
     *         phone: string,
     *         address_line_1: string,
     *         address_line_2: string|null,
     *         city: string,
     *         state_or_region: string|null,
     *         postal_code: string|null,
     *         country_code: string
     *     },
     *     items: list<array{
     *         product_name: string,
     *         sku: string|null,
     *         variant_description: string|null,
     *         unit_price: int,
     *         quantity: int,
     *         line_total: int
     *     }>,
     *     issued_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,

            'invoice_number' => $this->invoiceNumber,

            'order_number' => $this->orderNumber,

            'customer' => [
                'name' => $this->customerName,

                'email' => $this->customerEmail,
            ],

            'currency_code' => $this->currencyCode,

            'subtotal' => $this->subtotal,

            'discount_total' => $this->discountTotal,

            'delivery_total' => $this->deliveryTotal,

            'tax_total' => $this->taxTotal,

            'grand_total' => $this->grandTotal,

            'delivery_address' => $this
                ->deliveryAddress
                ->toArray(),

            'items' => array_map(
                static fn (
                    InvoiceItemData $item,
                ): array => $item
                    ->toArray(),
                $this->items,
            ),

            'issued_at' => $this->issuedAt,
        ];
    }
}
