<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProductPerformanceReportRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_route_is_protected_by_admin_authentication(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.products.index',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_filtered_summary_reconciles_with_matching_product_rows(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

        $completedPaid =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',

                status: OrderStatus::Completed,

                paymentStatus: PaymentStatus::Paid,
            );

        $completedPaidSecond =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-11 10:00:00',

                status: OrderStatus::Completed,

                paymentStatus: PaymentStatus::Paid,
            );

        $cancelled =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-12 10:00:00',

                status: OrderStatus::Cancelled,

                paymentStatus: PaymentStatus::Paid,
            );

        $this->createItem(
            order: $completedPaid,

            product: $firstProduct,

            name: 'Product A',

            sku: 'SKU-A',

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $completedPaidSecond,

            product: $firstProduct,

            name: 'Product A',

            sku: 'SKU-A',

            quantity: 3,

            lineTotal: 30_000,
        );

        $this->createItem(
            order: $completedPaid,

            product: $secondProduct,

            name: 'Product B',

            sku: 'SKU-B',

            quantity: 1,

            lineTotal: 15_000,
        );

        /*
         * Must not enter the filtered result.
         */
        $this->createItem(
            order: $cancelled,

            product: $firstProduct,

            name: 'Product A',

            sku: 'SKU-A',

            quantity: 100,

            lineTotal: 1_000_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',

                        'status' => [
                            'completed',
                        ],

                        'payment_status' => [
                            'paid',
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'report.meta.total',
                        2,
                    )
                    ->where(
                        'report.summary.product_groups_count',
                        2,
                    )
                    ->where(
                        'report.summary.quantity_sold',
                        6,
                    )
                    ->where(
                        'report.summary.orders_count',
                        2,
                    )
                    ->where(
                        'report.summary.gross_sales',
                        65_000,
                    )
                    ->where(
                        'report.data.0.product_name',
                        'Product A',
                    )
                    ->where(
                        'report.data.0.quantity_sold',
                        5,
                    )
                    ->where(
                        'report.data.0.orders_count',
                        2,
                    )
                    ->where(
                        'report.data.0.gross_sales',
                        50_000,
                    ),
            );
    }

    public function test_summary_is_not_limited_to_current_page(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',
            );

        for (
            $index = 1;
            $index <= 12;
            $index++
        ) {
            $product =
                Product::factory()
                    ->create();

            $this->createItem(
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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',

                        'per_page' => 10,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'report.meta.total',
                        12,
                    )
                    ->has(
                        'report.data',
                        10,
                    )
                    ->where(
                        'report.summary.product_groups_count',
                        12,
                    )
                    ->where(
                        'report.summary.quantity_sold',
                        12,
                    )
                    ->where(
                        'report.summary.orders_count',
                        1,
                    )
                    ->where(
                        'report.summary.gross_sales',
                        12_000,
                    ),
            );
    }

    public function test_deleted_product_snapshot_remains_safe_and_reportable(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',
            );

        $item =
            $this->createItem(
                order: $order,

                product: $product,

                name: 'Historical Widget',

                sku: 'OLD-WIDGET',

                quantity: 4,

                lineTotal: 40_000,
            );

        $product->delete();

        $item->refresh();

        $this->assertNull(
            $item->product_id,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'report.data.0.product_id',
                        null,
                    )
                    ->where(
                        'report.data.0.product_name',
                        'Historical Widget',
                    )
                    ->where(
                        'report.data.0.sku',
                        'OLD-WIDGET',
                    )
                    ->where(
                        'report.data.0.quantity_sold',
                        4,
                    )
                    ->where(
                        'report.data.0.gross_sales',
                        40_000,
                    ),
            );
    }

    public function test_product_search_cannot_change_authoritative_totals(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',
            );

        $matching =
            Product::factory()
                ->create();

        $other =
            Product::factory()
                ->create();

        $this->createItem(
            order: $order,

            product: $matching,

            name: 'Target Product',

            sku: 'TARGET-001',

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $order,

            product: $other,

            name: 'Other Product',

            sku: 'OTHER-001',

            quantity: 50,

            lineTotal: 500_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',

                        'search' => 'TARGET',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'report.meta.total',
                        1,
                    )
                    ->where(
                        'report.summary.quantity_sold',
                        2,
                    )
                    ->where(
                        'report.summary.gross_sales',
                        20_000,
                    )
                    ->where(
                        'report.data.0.sku',
                        'TARGET-001',
                    ),
            );
    }

    public function test_sidebar_exposes_product_performance_report_and_active_match(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/AdminSidebar.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "'admin.reports.products.index'",
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/products',
            $source,
        );

        $this->assertStringContainsString(
            'Product Performance',
            $source,
        );
    }

    public function test_frontend_does_not_link_historical_rows_to_current_catalog(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Products/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'row.product_id !==',
            $source,
        );

        $this->assertStringContainsString(
            "'admin.products.edit'",
            $source,
        );

        $this->assertMatchesRegularExpression(
            '/Historical\s+only/',
            $source,
        );
    }

    public function test_frontend_money_remains_minor_unit_aware(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Products/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'value / 100',
            $source,
        );

        $this->assertStringContainsString(
            'report',
            $source,
        );
    }

    private function createOrder(
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

    private function createItem(
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
