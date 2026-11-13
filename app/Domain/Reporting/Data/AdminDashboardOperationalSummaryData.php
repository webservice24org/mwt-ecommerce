<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class AdminDashboardOperationalSummaryData
{
    public function __construct(
        public int $pendingOrdersCount,

        public int $processingOrdersCount,

        public int $completedOrdersCount,

        public int $unfulfilledOrdersCount,

        public int $fulfilmentProcessingOrdersCount,

        public int $shippedOrdersCount,

        public int $deliveredOrdersCount,

        public int $returnedOrdersCount,

        public int $duePaymentOrdersCount,

        public int $duePaymentTotal,

        public int $unpaidPaymentOrdersCount,

        public int $unpaidPaymentTotal,
    ) {}

    /**
     * @return array{
     *     pending_orders_count: int,
     *     processing_orders_count: int,
     *     completed_orders_count: int,
     *     unfulfilled_orders_count: int,
     *     fulfilment_processing_orders_count: int,
     *     shipped_orders_count: int,
     *     delivered_orders_count: int,
     *     returned_orders_count: int,
     *     due_payment_orders_count: int,
     *     due_payment_total: int,
     *     unpaid_payment_orders_count: int,
     *     unpaid_payment_total: int
     * }
     */
    public function toArray(): array
    {
        return [
            'pending_orders_count' => $this->pendingOrdersCount,

            'processing_orders_count' => $this->processingOrdersCount,

            'completed_orders_count' => $this->completedOrdersCount,

            'unfulfilled_orders_count' => $this->unfulfilledOrdersCount,

            'fulfilment_processing_orders_count' => $this->fulfilmentProcessingOrdersCount,

            'shipped_orders_count' => $this->shippedOrdersCount,

            'delivered_orders_count' => $this->deliveredOrdersCount,

            'returned_orders_count' => $this->returnedOrdersCount,

            'due_payment_orders_count' => $this->duePaymentOrdersCount,

            'due_payment_total' => $this->duePaymentTotal,

            'unpaid_payment_orders_count' => $this->unpaidPaymentOrdersCount,

            'unpaid_payment_total' => $this->unpaidPaymentTotal,
        ];
    }
}
