<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class OperationalOrdersSummaryData
{
    public function __construct(
        public int $ordersCount,
        public int $pendingOrders,
        public int $processingOrders,
        public int $completedOrders,
        public int $unfulfilledOrders,
        public int $fulfilmentProcessingOrders,
        public int $shippedOrders,
        public int $deliveredOrders,
        public int $returnedOrders,
        public int $duePaymentOrders,
        public int $duePaymentTotal,
        public int $unpaidPaymentOrders,
        public int $unpaidPaymentTotal,
        public int $attentionRequiredOrders,
        public int $criticalAttentionOrders,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'orders_count' => $this->ordersCount,

            'pending_orders' => $this->pendingOrders,

            'processing_orders' => $this->processingOrders,

            'completed_orders' => $this->completedOrders,

            'unfulfilled_orders' => $this->unfulfilledOrders,

            'fulfilment_processing_orders' => $this->fulfilmentProcessingOrders,

            'shipped_orders' => $this->shippedOrders,

            'delivered_orders' => $this->deliveredOrders,

            'returned_orders' => $this->returnedOrders,

            'due_payment_orders' => $this->duePaymentOrders,

            'due_payment_total' => $this->duePaymentTotal,

            'unpaid_payment_orders' => $this->unpaidPaymentOrders,

            'unpaid_payment_total' => $this->unpaidPaymentTotal,
            'attention_required_orders' => $this->attentionRequiredOrders,

            'critical_attention_orders' => $this->criticalAttentionOrders,
        ];
    }
}
