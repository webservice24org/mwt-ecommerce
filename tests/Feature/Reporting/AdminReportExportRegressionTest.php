<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminProductPerformanceExportQuery;
use App\Domain\Reporting\Queries\GetAdminProductPerformanceReportQuery;
use App\Domain\Reporting\Queries\GetAdminSalesReportExportQuery;
use App\Domain\Reporting\Queries\GetAdminSalesReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminReportExportRegressionTest extends TestCase
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

    public function test_sales_export_count_matches_report_total(): void
    {
        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 7;
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
                            $index,
                        ),
                        'Asia/Dhaka',
                    ),
                ]);
        }

        $filters =
            new SalesReportFiltersData(
                range: $this->range(),
            );

        $report =
            app(
                GetAdminSalesReportQuery::class,
            )->get(
                filters: $filters,

                page: 1,
            );

        $exportCount =
            app(
                GetAdminSalesReportExportQuery::class,
            )->count(
                $filters,
            );

        $this->assertSame(
            $report->total,
            $exportCount,
        );
    }

    public function test_product_export_count_matches_report_group_total(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->order(
                $customer,
            );

        for (
            $index = 1;
            $index <= 6;
            $index++
        ) {
            $product =
                Product::factory()
                    ->create();

            $this->item(
                order: $order,

                product: $product,

                name: sprintf(
                    'Product %02d',
                    $index,
                ),

                sku: sprintf(
                    'SKU-%02d',
                    $index,
                ),

                quantity: 1,

                lineTotal: 1_000,
            );
        }

        $filters =
            new ProductPerformanceReportFiltersData(
                range: $this->range(),
            );

        $report =
            app(
                GetAdminProductPerformanceReportQuery::class,
            )->get(
                filters: $filters,

                page: 1,
            );

        $exportCount =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->count(
                $filters,
            );

        $this->assertSame(
            $report->total,
            $exportCount,
        );
    }

    public function test_sales_export_content_reconciles_with_filtered_report(): void
    {
        $customer =
            User::factory()
                ->create();

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'COMPLETED-001',

                'status' => OrderStatus::Completed,

                'payment_status' => PaymentStatus::Paid,

                'grand_total' => 25_000,

                'placed_at' => CarbonImmutable::parse(
                    '2026-11-10 10:00:00',
                    'Asia/Dhaka',
                ),
            ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'CANCELLED-001',

                'status' => OrderStatus::Cancelled,

                'payment_status' => PaymentStatus::Paid,

                'grand_total' => 100_000,

                'placed_at' => CarbonImmutable::parse(
                    '2026-11-11 10:00:00',
                    'Asia/Dhaka',
                ),
            ]);

        $filters =
            new SalesReportFiltersData(
                range: $this->range(),

                statuses: [
                    OrderStatus::Completed,
                ],

                paymentStatuses: [
                    PaymentStatus::Paid,
                ],
            );

        $report =
            app(
                GetAdminSalesReportQuery::class,
            )->get(
                filters: $filters,

                page: 1,
            );

        $export =
            app(
                GetAdminSalesReportExportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            1,
            $report->total,
        );

        $this->assertSame(
            1,
            $export->rowCount(),
        );

        $this->assertSame(
            'COMPLETED-001',
            $export->rows[0]
                ->orderNumber,
        );

        $this->assertSame(
            $report->rows[0]
                ->grandTotal,
            $export->rows[0]
                ->grandTotal,
        );
    }

    public function test_product_export_values_reconcile_with_report_values(): void
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

        $filters =
            new ProductPerformanceReportFiltersData(
                range: $this->range(),
            );

        $report =
            app(
                GetAdminProductPerformanceReportQuery::class,
            )->get(
                filters: $filters,

                page: 1,
            );

        $export =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            1,
            $export->rowCount(),
        );

        $this->assertSame(
            $report->rows[0]
                ->quantitySold,
            $export->rows[0]
                ->quantitySold,
        );

        $this->assertSame(
            $report->rows[0]
                ->ordersCount,
            $export->rows[0]
                ->ordersCount,
        );

        $this->assertSame(
            $report->rows[0]
                ->grossSales,
            $export->rows[0]
                ->grossSales,
        );
    }

    public function test_deleted_product_remains_consistent_between_report_and_export(): void
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
            );

        $item =
            $this->item(
                $order,
                $product,
                'Historical Product',
                'OLD-001',
                2,
                20_000,
            );

        $product->delete();

        $item->refresh();

        $filters =
            new ProductPerformanceReportFiltersData(
                range: $this->range(),
            );

        $report =
            app(
                GetAdminProductPerformanceReportQuery::class,
            )->get(
                $filters,
            );

        $export =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                $filters,
            );

        $this->assertNull(
            $report->rows[0]
                ->productId,
        );

        $this->assertNull(
            $export->rows[0]
                ->productId,
        );

        $this->assertTrue(
            $export->rows[0]
                ->historical,
        );

        $this->assertSame(
            $report->rows[0]
                ->productName,
            $export->rows[0]
                ->productName,
        );
    }

    public function test_sales_endpoint_rejects_export_over_configured_limit(): void
    {
        config([
            'reporting.exports.max_rows' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 3;
            $index++
        ) {
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.sales.export',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertStatus(
                413,
            );
    }

    public function test_product_endpoint_rejects_export_over_configured_limit(): void
    {
        config([
            'reporting.exports.max_rows' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $order =
            $this->order(
                $customer,
            );

        for (
            $index = 1;
            $index <= 3;
            $index++
        ) {
            $product =
                Product::factory()
                    ->create();

            $this->item(
                $order,
                $product,
                sprintf(
                    'Product %d',
                    $index,
                ),
                sprintf(
                    'SKU-%d',
                    $index,
                ),
                1,
                1_000,
            );
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.export',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertStatus(
                413,
            );
    }

    public function test_export_routes_remain_admin_protected(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.sales.export',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );

        $this
            ->get(
                route(
                    'admin.reports.products.export',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
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
        string $placedAt =
        '2026-11-10 10:00:00',
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
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
