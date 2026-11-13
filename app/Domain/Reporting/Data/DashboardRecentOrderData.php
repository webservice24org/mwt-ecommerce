<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class DashboardRecentOrderData
{
    public function __construct(
        public int $id,

        public string $orderNumber,

        public string $customerName,

        public string $status,

        public string $statusLabel,

        public string $paymentStatus,

        public string $paymentStatusLabel,

        public string $fulfilmentStatus,

        public string $fulfilmentStatusLabel,

        public string $currencyCode,

        public int $grandTotal,

        public string $placedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     order_number: string,
     *     customer_name: string,
     *     status: string,
     *     status_label: string,
     *     payment_status: string,
     *     payment_status_label: string,
     *     fulfilment_status: string,
     *     fulfilment_status_label: string,
     *     currency_code: string,
     *     grand_total: int,
     *     placed_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,

            'order_number' => $this->orderNumber,

            'customer_name' => $this->customerName,

            'status' => $this->status,

            'status_label' => $this->statusLabel,

            'payment_status' => $this->paymentStatus,

            'payment_status_label' => $this->paymentStatusLabel,

            'fulfilment_status' => $this->fulfilmentStatus,

            'fulfilment_status_label' => $this->fulfilmentStatusLabel,

            'currency_code' => $this->currencyCode,

            'grand_total' => $this->grandTotal,

            'placed_at' => $this->placedAt,
        ];
    }
}
