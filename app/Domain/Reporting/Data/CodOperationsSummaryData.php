<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class CodOperationsSummaryData
{
    public function __construct(
        public int $ordersCount,
        public int $codOrdersCount,

        public int $paidOrdersCount,
        public int $paidTotal,

        public int $dueOrdersCount,
        public int $dueTotal,

        public int $unpaidOrdersCount,
        public int $unpaidTotal,

        public int $deliveredDueOrdersCount,
        public int $deliveredDueTotal,

        public int $deliveredUnpaidOrdersCount,
        public int $deliveredUnpaidTotal,

        public int $followUpOrdersCount,
        public int $criticalRiskOrdersCount,
        public int $highRiskOrdersCount,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'orders_count' => $this->ordersCount,

            'cod_orders_count' => $this->codOrdersCount,

            'paid_orders_count' => $this->paidOrdersCount,

            'paid_total' => $this->paidTotal,

            'due_orders_count' => $this->dueOrdersCount,

            'due_total' => $this->dueTotal,

            'unpaid_orders_count' => $this->unpaidOrdersCount,

            'unpaid_total' => $this->unpaidTotal,

            'delivered_due_orders_count' => $this->deliveredDueOrdersCount,

            'delivered_due_total' => $this->deliveredDueTotal,

            'delivered_unpaid_orders_count' => $this->deliveredUnpaidOrdersCount,

            'delivered_unpaid_total' => $this->deliveredUnpaidTotal,

            'follow_up_orders_count' => $this->followUpOrdersCount,

            'critical_risk_orders_count' => $this->criticalRiskOrdersCount,

            'high_risk_orders_count' => $this->highRiskOrdersCount,
        ];
    }
}
