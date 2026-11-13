<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class AdminDashboardMetricsData
{
    public function __construct(
        public int $ordersCount,

        public int $grossSales,

        public int $discountTotal,

        public int $shippingTotal,

        public int $taxTotal,

        public int $netSales,

        public int $paidOrdersCount,

        public int $paidTotal,

        public int $dueOrdersCount,

        public int $dueTotal,

        public int $unpaidOrdersCount,

        public int $unpaidTotal,

        public int $productsSold,

        public int $customersCount,

        public int $averageOrderValue,
    ) {}

    /**
     * @return array{
     *     orders_count: int,
     *     gross_sales: int,
     *     discount_total: int,
     *     shipping_total: int,
     *     tax_total: int,
     *     net_sales: int,
     *     paid_orders_count: int,
     *     paid_total: int,
     *     due_orders_count: int,
     *     due_total: int,
     *     unpaid_orders_count: int,
     *     unpaid_total: int,
     *     products_sold: int,
     *     customers_count: int,
     *     average_order_value: int
     * }
     */
    public function toArray(): array
    {
        return [
            'orders_count' => $this->ordersCount,

            'gross_sales' => $this->grossSales,

            'discount_total' => $this->discountTotal,

            'shipping_total' => $this->shippingTotal,

            'tax_total' => $this->taxTotal,

            'net_sales' => $this->netSales,

            'paid_orders_count' => $this->paidOrdersCount,

            'paid_total' => $this->paidTotal,

            'due_orders_count' => $this->dueOrdersCount,

            'due_total' => $this->dueTotal,

            'unpaid_orders_count' => $this->unpaidOrdersCount,

            'unpaid_total' => $this->unpaidTotal,

            'products_sold' => $this->productsSold,

            'customers_count' => $this->customersCount,

            'average_order_value' => $this->averageOrderValue,
        ];
    }
}
