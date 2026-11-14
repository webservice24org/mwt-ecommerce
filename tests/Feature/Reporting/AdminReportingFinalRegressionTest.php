<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminProductPerformanceExportQuery;
use App\Domain\Reporting\Queries\GetAdminProductPerformanceReportQuery;
use App\Domain\Reporting\Queries\GetAdminSalesReportExportQuery;
use App\Domain\Reporting\Queries\GetAdminSalesReportQuery;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminReportingFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_routes_are_protected_from_guests(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.sales.index',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.products.index',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.sales.export',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.products.export',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_all_reporting_routes(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.sales.index',
                ),
            )
            ->assertOk();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
                ),
            )
            ->assertOk();
    }

    public function test_sales_report_and_export_reconcile_for_same_filters(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'Reporting Customer',
                ]);

        $firstOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'MWT-REPORT-001',
                placedAt: '2026-11-05 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
                subtotal: 10_000,
                discountTotal: 1_000,
                deliveryTotal: 800,
                taxTotal: 200,
                grandTotal: 10_000,
            );

        $this->createItem(
            order: $firstOrder,
            productName: 'Product A',
            sku: 'SKU-A',
            unitPrice: 5_000,
            quantity: 2,
            lineTotal: 10_000,
        );

        $secondOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'MWT-REPORT-002',
                placedAt: '2026-11-06 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
                subtotal: 20_000,
                discountTotal: 2_000,
                deliveryTotal: 1_200,
                taxTotal: 300,
                grandTotal: 19_500,
            );

        $this->createItem(
            order: $secondOrder,
            productName: 'Product B',
            sku: 'SKU-B',
            unitPrice: 20_000,
            quantity: 1,
            lineTotal: 20_000,
        );

        $filters =
            $this->salesFilters();

        $report =
            app(
                GetAdminSalesReportQuery::class,
            )->get(
                $filters,
                1,
            );

        $export =
            app(
                GetAdminSalesReportExportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            $report->total,
            $export->rowCount(),
        );

        $this->assertSame(
            2,
            $report->total,
        );

        $this->assertSame(
            30_000,
            $report->subtotal,
        );

        $this->assertSame(
            3_000,
            $report->discountTotal,
        );

        $this->assertSame(
            2_000,
            $report->deliveryTotal,
        );

        $this->assertSame(
            500,
            $report->taxTotal,
        );

        $this->assertSame(
            29_500,
            $report->grandTotal,
        );

        $this->assertSame(
            3,
            $report->itemsQuantity,
        );

        $exportRows =
            $export->rowsAsArrays();

        $this->assertSame(
            'MWT-REPORT-002',
            $exportRows[0][
                'order_number'
            ],
        );

        $this->assertSame(
            'MWT-REPORT-001',
            $exportRows[1][
                'order_number'
            ],
        );
    }

    public function test_product_report_and_export_reconcile_for_same_filters(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Tracked Product',
                ]);

        $firstOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'MWT-PRODUCT-001',
                placedAt: '2026-11-07 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
                subtotal: 15_000,
                grandTotal: 15_000,
            );

        $this->createItem(
            order: $firstOrder,
            product: $product,
            productName: 'Tracked Product',
            sku: 'TRACK-001',
            unitPrice: 5_000,
            quantity: 3,
            lineTotal: 15_000,
        );

        $secondOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'MWT-PRODUCT-002',
                placedAt: '2026-11-08 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
                subtotal: 10_000,
                grandTotal: 10_000,
            );

        $this->createItem(
            order: $secondOrder,
            product: $product,
            productName: 'Tracked Product',
            sku: 'TRACK-001',
            unitPrice: 5_000,
            quantity: 2,
            lineTotal: 10_000,
        );

        $filters =
            $this->productFilters();

        $report =
            app(
                GetAdminProductPerformanceReportQuery::class,
            )->get(
                $filters,
                1,
            );

        $export =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            $report->total,
            $export->rowCount(),
        );

        $this->assertSame(
            1,
            $report->total,
        );

        $this->assertSame(
            1,
            $report->productGroupsCount,
        );

        $this->assertSame(
            5,
            $report->quantitySold,
        );

        $this->assertSame(
            2,
            $report->ordersCount,
        );

        $this->assertSame(
            25_000,
            $report->grossSales,
        );

        $row =
            $export->rowsAsArrays()[0];

        $this->assertSame(
            'Tracked Product',
            $row[
                'product_name'
            ],
        );

        $this->assertSame(
            'TRACK-001',
            $row[
                'sku'
            ],
        );

        $this->assertSame(
            5,
            $row[
                'quantity_sold'
            ],
        );

        $this->assertSame(
            2,
            $row[
                'orders_count'
            ],
        );

        $this->assertSame(
            25_000,
            $row[
                'gross_sales'
            ],
        );
    }

    public function test_cancelled_orders_are_not_silently_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $cancelled =
            $this->createOrder(
                user: $customer,
                orderNumber: 'MWT-CANCELLED-001',
                placedAt: '2026-11-09 10:00:00',
                status: OrderStatus::Cancelled,
                paymentStatus: PaymentStatus::Unpaid,
                subtotal: 9_000,
                grandTotal: 9_000,
            );

        $this->createItem(
            order: $cancelled,
            productName: 'Cancelled Product',
            sku: 'CANCEL-001',
            unitPrice: 9_000,
            quantity: 1,
            lineTotal: 9_000,
        );

        $sales =
            app(
                GetAdminSalesReportQuery::class,
            )->get(
                $this->salesFilters(),
                1,
            );

        $products =
            app(
                GetAdminProductPerformanceReportQuery::class,
            )->get(
                $this->productFilters(),
                1,
            );

        $this->assertSame(
            1,
            $sales->total,
        );

        $this->assertSame(
            1,
            $products->total,
        );
    }

    public function test_deleted_products_remain_available_in_historical_reporting(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Historical Product',
                ]);

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'MWT-HISTORY-001',
                placedAt: '2026-11-10 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
                subtotal: 7_500,
                grandTotal: 7_500,
            );

        $item =
            $this->createItem(
                order: $order,
                product: $product,
                productName: 'Historical Product',
                sku: 'HIST-001',
                unitPrice: 7_500,
                quantity: 1,
                lineTotal: 7_500,
            );

        $item->update([
            'product_id' => null,
        ]);

        $product->delete();

        $report =
            app(
                GetAdminProductPerformanceReportQuery::class,
            )->get(
                $this->productFilters(),
                1,
            );

        $export =
            app(
                GetAdminProductPerformanceExportQuery::class,
            )->get(
                $this->productFilters(),
            );

        $this->assertSame(
            1,
            $report->total,
        );

        $reportRow =
            $report->rows[0];

        $this->assertNull(
            $reportRow->productId,
        );

        $this->assertSame(
            'Historical Product',
            $reportRow->productName,
        );

        $exportRow =
            $export->rowsAsArrays()[0];

        $this->assertNull(
            $exportRow[
                'product_id'
            ],
        );

        $this->assertSame(
            1,
            $exportRow[
                'historical'
            ],
        );
    }

    public function test_reporting_frontend_contracts_remain_present(): void
    {
        $sales =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Sales/Index.tsx',
                ),
            );

        $products =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Products/Index.tsx',
                ),
            );

        $header =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/Reports/ReportHeader.tsx',
                ),
            );

        $navigation =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/Reports/ReportNavigation.tsx',
                ),
            );

        $this->assertIsString(
            $sales,
        );

        $this->assertIsString(
            $products,
        );

        $this->assertIsString(
            $header,
        );

        $this->assertIsString(
            $navigation,
        );

        $this->assertStringContainsString(
            'activeReport="sales"',
            $sales,
        );

        $this->assertStringContainsString(
            'activeReport="products"',
            $products,
        );

        $this->assertStringContainsString(
            'Reset report filters',
            $sales,
        );

        $this->assertStringContainsString(
            'Reset report filters',
            $products,
        );

        $this->assertStringContainsString(
            'Preparing export...',
            $header,
        );

        $this->assertStringContainsString(
            'aria-live="assertive"',
            $header,
        );

        $this->assertStringContainsString(
            'aria-current',
            $navigation,
        );
    }

    private function salesFilters(): SalesReportFiltersData
    {
        return new SalesReportFiltersData(
            range: $this->range(),
            statuses: [],
            paymentStatuses: [],
            search: null,
            perPage: 25,
        );
    }

    private function productFilters(): ProductPerformanceReportFiltersData
    {
        return new ProductPerformanceReportFiltersData(
            range: $this->range(),
            statuses: [],
            paymentStatuses: [],
            search: null,
            perPage: 25,
        );
    }

    private function range(): ReportDateRangeData
    {
        return new ReportDateRangeData(
            preset: ReportDatePreset::Custom,
            fromInclusive: CarbonImmutable::parse(
                '2026-11-01 00:00:00',
                'Asia/Dhaka',
            ),
            toExclusive: CarbonImmutable::parse(
                '2026-12-01 00:00:00',
                'Asia/Dhaka',
            ),
            timezone: 'Asia/Dhaka',
        );
    }

    private function createOrder(
        User $user,
        string $orderNumber,
        string $placedAt,
        OrderStatus $status,
        PaymentStatus $paymentStatus,
        int $subtotal,
        int $grandTotal,
        int $discountTotal = 0,
        int $deliveryTotal = 0,
        int $taxTotal = 0,
    ): Order {
        return Order::factory()
            ->for(
                $user,
            )
            ->create([
                'order_number' => $orderNumber,

                'status' => $status,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => $paymentStatus,

                'fulfilment_status' => FulfilmentStatus::Delivered,

                'currency_code' => 'BDT',

                'subtotal' => $subtotal,

                'discount_total' => $discountTotal,

                'delivery_total' => $deliveryTotal,

                'tax_total' => $taxTotal,

                'grand_total' => $grandTotal,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function createItem(
        Order $order,
        string $productName,
        ?string $sku,
        int $unitPrice,
        int $quantity,
        int $lineTotal,
        ?Product $product = null,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_id' => $product?->id,

                'product_name' => $productName,

                'sku' => $sku,

                'unit_price' => $unitPrice,

                'quantity' => $quantity,

                'line_total' => $lineTotal,
            ]);
    }
}
