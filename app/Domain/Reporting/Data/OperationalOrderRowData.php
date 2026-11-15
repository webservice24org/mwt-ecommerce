<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class OperationalOrderRowData
{
    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public ?int $customerId,
        public string $customerName,
        public string $status,
        public string $paymentStatus,
        public string $paymentMethod,
        public string $fulfilmentStatus,
        public string $currencyCode,
        public int $grandTotal,
        public int $amountDue,
        public ?string $paymentDueAt,
        public ?string $paidAt,
        public string $placedAt,

        public int $ageHours,
        public int $ageDays,

        public OperationalOrderAttentionData $attention,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,

            'order_number' => $this->orderNumber,

            'customer_id' => $this->customerId,

            'customer_name' => $this->customerName,

            'status' => $this->status,

            'payment_status' => $this->paymentStatus,

            'payment_method' => $this->paymentMethod,

            'fulfilment_status' => $this->fulfilmentStatus,

            'currency_code' => $this->currencyCode,

            'grand_total' => $this->grandTotal,

            'amount_due' => $this->amountDue,

            'payment_due_at' => $this->paymentDueAt,

            'paid_at' => $this->paidAt,

            'placed_at' => $this->placedAt,

            'age_hours' => $this->ageHours,

            'age_days' => $this->ageDays,

            'attention' => $this->attention->toArray(),
        ];
    }
}
