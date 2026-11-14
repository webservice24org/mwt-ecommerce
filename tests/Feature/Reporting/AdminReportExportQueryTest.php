<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminProductPerformanceExportQuery;
use App\Domain\Reporting\Queries\GetAdminSalesReportExportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminReportExportQueryTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $dateRanges;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dateRanges =
            app(
                ReportDateRangeFactory::class,
            );
    }

    public function test_sales_export_is_not_paginated(): void
    {
        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 30;
            $index++
        ) {
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'placed_at' => CarbonImmutable::parse(
                        sprintf(
                            '2026-11-%02d 10:00:00',
                            (($index - 1) % 28) + 1,
                        ),
                        'Asia/Dhaka',
                    ),
                ]);
        }

        $result =
            app(
                GetAdminSalesReportExportQuery::class,
            )->get(
                new SalesReportFiltersData(
                    range: $this->range(),
                    perPage: 10,
                ),
            );

        $this->assertSame(
            30,
            $result->rowCount(),
        );
    }

    public function test_sales_export_has_deterministic_column_order(): void
    {
        $result =
            app(
                GetAdminSalesReportExportQuery::class,
            )->get(
                new SalesReportFiltersData(
                    range: $this->range(),
                ),
            );

        $this->assertSame(
            [
                'order_id',
                'order_number',
                'customer_id',
                'customer_name',
                'status',
                'status_label',
                'payment_status',
                'payment_status_label',
                'fulfilment_status',
                'fulfilment_status_label',
                'currency_code',
                'subtotal',
                'discount_total',
                'delivery_total',
                'tax_total',
                'grand_total',
                'items_quantity',
                'placed_at',
            ],
            $result->columns,
        );
    }

    public function test_product_export_aggregates_matching_product_and_sku(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $first =
            $this->order(
                $customer,
                '2026-11-10 10:00:00',
            );

        $second =
            $this->order(
                $customer,
                '2026-11-11 10:00:00',
            );

        $this->item(
            $first,
            $product,
            'Product A',
            'SKU-A',
            2,
            20_000,
        );

        $this->item(
            $second,
            $product,
            'Product A',
            'SKU-A',
            3,
            30_000,
        );

        $result =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                new ProductPerformanceReportFiltersData(
                    range: $this->range(),
                ),
            );

        $this->assertSame(
            1,
            $result->rowCount(),
        );

        $this->assertSame(
            5,
            $result->rows[0]
                ->quantitySold,
        );

        $this->assertSame(
            50_000,
            $result->rows[0]
                ->grossSales,
        );
    }

    public function test_product_export_preserves_deleted_product_history(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->order(
                $customer,
                '2026-11-10 10:00:00',
            );

        $item =
            $this->item(
                $order,
                $product,
                'Deleted Product',
                'OLD-001',
                2,
                20_000,
            );

        $product->delete();

        $item->refresh();

        $this->assertNull(
            $item->product_id,
        );

        $result =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                new ProductPerformanceReportFiltersData(
                    range: $this->range(),
                ),
            );

        $this->assertSame(
            1,
            $result->rowCount(),
        );

        $this->assertNull(
            $result->rows[0]
                ->productId,
        );

        $this->assertTrue(
            $result->rows[0]
                ->historical,
        );

        $this->assertSame(
            'Deleted Product',
            $result->rows[0]
                ->productName,
        );

        $this->assertSame(
            'OLD-001',
            $result->rows[0]
                ->sku,
        );
    }

    public function test_product_export_uses_deterministic_columns(): void
    {
        $result =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                new ProductPerformanceReportFiltersData(
                    range: $this->range(),
                ),
            );

        $this->assertSame(
            [
                'product_id',
                'product_name',
                'sku',
                'historical',
                'quantity_sold',
                'orders_count',
                'gross_sales',
            ],
            $result->columns,
        );
    }

    public function test_export_filters_match_report_filter_semantics(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $completed =
            $this->order(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',

                status: OrderStatus::Completed,

                paymentStatus: PaymentStatus::Paid,
            );

        $cancelled =
            $this->order(
                customer: $customer,

                placedAt: '2026-11-10 11:00:00',

                status: OrderStatus::Cancelled,

                paymentStatus: PaymentStatus::Paid,
            );

        $this->item(
            $completed,
            $product,
            'Target Product',
            'TARGET',
            2,
            20_000,
        );

        $this->item(
            $cancelled,
            $product,
            'Target Product',
            'TARGET',
            20,
            200_000,
        );

        $result =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                new ProductPerformanceReportFiltersData(
                    range: $this->range(),

                    statuses: [
                        OrderStatus::Completed,
                    ],

                    paymentStatuses: [
                        PaymentStatus::Paid,
                    ],

                    search: 'TARGET',
                ),
            );

        $this->assertSame(
            1,
            $result->rowCount(),
        );

        $this->assertSame(
            2,
            $result->rows[0]
                ->quantitySold,
        );

        $this->assertSame(
            20_000,
            $result->rows[0]
                ->grossSales,
        );
    }

    private function range()
    {
        return $this
            ->dateRanges
            ->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-30',

                timezone: 'Asia/Dhaka',
            );
    }

    private function order(
        User $customer,
        string $placedAt,
        OrderStatus $status =
        OrderStatus::Processing,
        PaymentStatus $paymentStatus =
        PaymentStatus::Unpaid,
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => $status,

                'payment_status' => $paymentStatus,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function item(
        Order $order,
        Product $product,
        string $name,
        ?string $sku,
        int $quantity,
        int $lineTotal,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->for(
                $product,
            )
            ->create([
                'product_name' => $name,

                'sku' => $sku,

                'quantity' => $quantity,

                'unit_price' => intdiv(
                    $lineTotal,
                    $quantity,
                ),

                'line_total' => $lineTotal,
            ]);
    }
}
