<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class SalesReportRowData
{
    public function __construct(
        public int $id,

        public string $orderNumber,

        public int $customerId,

        public string $customerName,

        public string $status,

        public string $statusLabel,

        public string $paymentStatus,

        public string $paymentStatusLabel,

        public string $fulfilmentStatus,

        public string $fulfilmentStatusLabel,

        public string $currencyCode,

        public int $subtotal,

        public int $discountTotal,

        public int $deliveryTotal,

        public int $taxTotal,

        public int $grandTotal,

        public int $itemsQuantity,

        public string $placedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     order_number: string,
     *     customer_id: int,
     *     customer_name: string,
     *     status: string,
     *     status_label: string,
     *     payment_status: string,
     *     payment_status_label: string,
     *     fulfilment_status: string,
     *     fulfilment_status_label: string,
     *     currency_code: string,
     *     subtotal: int,
     *     discount_total: int,
     *     delivery_total: int,
     *     tax_total: int,
     *     grand_total: int,
     *     items_quantity: int,
     *     placed_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,

            'order_number' => $this->orderNumber,

            'customer_id' => $this->customerId,

            'customer_name' => $this->customerName,

            'status' => $this->status,

            'status_label' => $this->statusLabel,

            'payment_status' => $this->paymentStatus,

            'payment_status_label' => $this->paymentStatusLabel,

            'fulfilment_status' => $this->fulfilmentStatus,

            'fulfilment_status_label' => $this->fulfilmentStatusLabel,

            'currency_code' => $this->currencyCode,

            'subtotal' => $this->subtotal,

            'discount_total' => $this->discountTotal,

            'delivery_total' => $this->deliveryTotal,

            'tax_total' => $this->taxTotal,

            'grand_total' => $this->grandTotal,

            'items_quantity' => $this->itemsQuantity,

            'placed_at' => $this->placedAt,
        ];
    }
}
