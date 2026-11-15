<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
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

final class AdminProductAnalyticsReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_product_analytics_report(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.products.analytics',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_product_analytics_report(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.products.analytics',
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Reports/ProductAnalytics/Index',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'query',
                    )
                    ->has(
                        'options',
                    )
                    ->has(
                        'report.summary',
                    )
                    ->has(
                        'report.products',
                    )
                    ->has(
                        'charts.sales_trend',
                    )
                    ->has(
                        'charts.top_products_by_revenue',
                    )
                    ->has(
                        'charts.top_products_by_quantity',
                    )
                    ->has(
                        'charts.current_vs_historical_revenue',
                    )
                    ->has(
                        'charts.sku_performance',
                    )
                    ->has(
                        'charts.revenue_share_by_product',
                    ),
            );
    }

    public function test_product_analytics_report_exposes_authoritative_summary_and_chart_data(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Analytics Product',
                ]);

        $order =
            $this->createOrder(
                user: $customer,

                orderNumber: 'PRODUCT-ANALYTICS-001',

                placedAt: '2026-11-10 10:00:00',

                status: OrderStatus::Completed,

                paymentStatus: PaymentStatus::Paid,
            );

        $this->createItem(
            order: $order,

            product: $product,

            productName: 'Analytics Product',

            sku: 'ANALYTICS-SKU',

            quantity: 3,

            lineTotal: 15_000,
        );

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.products.analytics',
                        [
                            'preset' => 'custom',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',

                            'timezone' => 'Asia/Dhaka',
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'report.summary.product_groups_count',
                        1,
                    )
                    ->where(
                        'report.summary.quantity_sold',
                        3,
                    )
                    ->where(
                        'report.summary.orders_count',
                        1,
                    )
                    ->where(
                        'report.summary.gross_sales',
                        15_000,
                    )
                    ->where(
                        'report.summary.current_product_revenue',
                        15_000,
                    )
                    ->where(
                        'report.summary.historical_product_revenue',
                        0,
                    )
                    ->where(
                        'report.products.0.product_name',
                        'Analytics Product',
                    )
                    ->where(
                        'report.products.0.sku',
                        'ANALYTICS-SKU',
                    )
                    ->where(
                        'report.products.0.quantity_sold',
                        3,
                    )
                    ->where(
                        'report.products.0.gross_sales',
                        15_000,
                    )
                    ->has(
                        'charts.sales_trend.points',
                        1,
                    )
                    ->has(
                        'charts.top_products_by_revenue.points',
                        1,
                    )
                    ->has(
                        'charts.top_products_by_quantity.points',
                        1,
                    ),
            );
    }

    public function test_product_analytics_preserves_date_search_status_and_payment_filters(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $matchingProduct =
            Product::factory()
                ->create([
                    'name' => 'Special Product',
                ]);

        $otherProduct =
            Product::factory()
                ->create([
                    'name' => 'Other Product',
                ]);

        $matchingOrder =
            $this->createOrder(
                user: $customer,

                orderNumber: 'MATCHING-001',

                placedAt: '2026-11-12 10:00:00',

                status: OrderStatus::Completed,

                paymentStatus: PaymentStatus::Paid,
            );

        $this->createItem(
            order: $matchingOrder,

            product: $matchingProduct,

            productName: 'Special Product',

            sku: 'SPECIAL-SKU',

            quantity: 2,

            lineTotal: 10_000,
        );

        $otherOrder =
            $this->createOrder(
                user: $customer,

                orderNumber: 'OTHER-001',

                placedAt: '2026-11-13 10:00:00',

                status: OrderStatus::Processing,

                paymentStatus: PaymentStatus::Due,
            );

        $this->createItem(
            order: $otherOrder,

            product: $otherProduct,

            productName: 'Other Product',

            sku: 'OTHER-SKU',

            quantity: 4,

            lineTotal: 20_000,
        );

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.products.analytics',
                        [
                            'preset' => 'custom',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',

                            'timezone' => 'Asia/Dhaka',

                            'status' => [
                                OrderStatus::Completed->value,
                            ],

                            'payment_status' => [
                                PaymentStatus::Paid->value,
                            ],

                            'search' => 'SPECIAL-SKU',
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'report.summary.product_groups_count',
                        1,
                    )
                    ->where(
                        'report.summary.quantity_sold',
                        2,
                    )
                    ->where(
                        'report.summary.orders_count',
                        1,
                    )
                    ->where(
                        'report.summary.gross_sales',
                        10_000,
                    )
                    ->where(
                        'report.products.0.product_name',
                        'Special Product',
                    )
                    ->where(
                        'report.products.0.sku',
                        'SPECIAL-SKU',
                    )
                    ->where(
                        'query.search',
                        'SPECIAL-SKU',
                    ),
            );
    }

    public function test_historical_deleted_product_snapshot_is_exposed_in_product_analytics(): void
    {
        $admin =
            Admin::factory()
                ->create();

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

                orderNumber: 'HISTORICAL-001',

                placedAt: '2026-11-15 10:00:00',

                status: OrderStatus::Completed,

                paymentStatus: PaymentStatus::Paid,
            );

        $item =
            $this->createItem(
                order: $order,

                product: $product,

                productName: 'Historical Product',

                sku: 'HIST-SKU',

                quantity: 2,

                lineTotal: 12_000,
            );

        $item->update([
            'product_id' => null,
        ]);

        $product->delete();

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.products.analytics',
                        [
                            'preset' => 'custom',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',

                            'timezone' => 'Asia/Dhaka',
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'report.summary.current_product_revenue',
                        0,
                    )
                    ->where(
                        'report.summary.historical_product_revenue',
                        12_000,
                    )
                    ->where(
                        'report.products.0.product_id',
                        null,
                    )
                    ->where(
                        'report.products.0.product_name',
                        'Historical Product',
                    )
                    ->where(
                        'report.products.0.sku',
                        'HIST-SKU',
                    )
                    ->where(
                        'report.products.0.historical',
                        true,
                    ),
            );
    }

    public function test_cancelled_orders_are_not_implicitly_excluded(): void
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
                user: $customer,

                orderNumber: 'CANCELLED-ANALYTICS',

                placedAt: '2026-11-16 10:00:00',

                status: OrderStatus::Cancelled,

                paymentStatus: PaymentStatus::Unpaid,
            );

        $this->createItem(
            order: $order,

            product: $product,

            productName: 'Cancelled Product',

            sku: 'CANCELLED-SKU',

            quantity: 1,

            lineTotal: 7_000,
        );

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.products.analytics',
                        [
                            'preset' => 'custom',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',

                            'timezone' => 'Asia/Dhaka',
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'report.summary.orders_count',
                        1,
                    )
                    ->where(
                        'report.summary.gross_sales',
                        7_000,
                    ),
            );
    }

    private function createOrder(
        User $user,
        string $orderNumber,
        string $placedAt,
        OrderStatus $status,
        PaymentStatus $paymentStatus,
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

                'subtotal' => 0,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 0,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function createItem(
        Order $order,
        ?Product $product,
        string $productName,
        ?string $sku,
        int $quantity,
        int $lineTotal,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_id' => $product?->id,

                'product_name' => $productName,

                'sku' => $sku,

                'unit_price' => intdiv(
                    $lineTotal,
                    $quantity,
                ),

                'quantity' => $quantity,

                'line_total' => $lineTotal,
            ]);
    }
}
