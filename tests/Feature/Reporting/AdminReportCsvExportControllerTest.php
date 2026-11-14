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
use Tests\TestCase;

final class AdminReportCsvExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_export_requires_admin_authentication(): void
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
    }

    public function test_product_export_requires_admin_authentication(): void
    {
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

    public function test_sales_export_returns_csv_attachment(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'MWT-EXPORT-001',

                'placed_at' => CarbonImmutable::parse(
                    '2026-11-10 10:00:00',
                    'Asia/Dhaka',
                ),
            ]);

        $response =
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
                );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'text/csv; charset=UTF-8',
            )
            ->assertHeader(
                'Content-Disposition',
                'attachment; filename="sales-report-2026-11-01-to-2026-11-30.csv"',
            )
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff',
            );

        $content =
            $response
                ->getContent();

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $content,
        );

        $this->assertStringContainsString(
            'MWT-EXPORT-001',
            $content,
        );
    }

    public function test_sales_export_preserves_report_filters(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'PAID-ORDER',

                'status' => OrderStatus::Completed,

                'payment_status' => PaymentStatus::Paid,

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
                'order_number' => 'CANCELLED-ORDER',

                'status' => OrderStatus::Cancelled,

                'payment_status' => PaymentStatus::Paid,

                'placed_at' => CarbonImmutable::parse(
                    '2026-11-10 11:00:00',
                    'Asia/Dhaka',
                ),
            ]);

        $response =
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

                            'status' => [
                                'completed',
                            ],

                            'payment_status' => [
                                'paid',
                            ],
                        ],
                    ),
                );

        $content =
            $response
                ->assertOk()
                ->getContent();

        $this->assertStringContainsString(
            'PAID-ORDER',
            $content,
        );

        $this->assertStringNotContainsString(
            'CANCELLED-ORDER',
            $content,
        );
    }

    public function test_product_export_returns_csv_attachment_and_historical_rows(): void
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

        $item =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->for(
                    $product,
                )
                ->create([
                    'product_name' => 'Historical Product',

                    'sku' => 'OLD-SKU',

                    'quantity' => 2,

                    'unit_price' => 10_000,

                    'line_total' => 20_000,
                ]);

        $product->delete();

        $item->refresh();

        $this->assertNull(
            $item->product_id,
        );

        $response =
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
                );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'text/csv; charset=UTF-8',
            )
            ->assertHeader(
                'Content-Disposition',
                'attachment; filename="product-performance-report-2026-11-01-to-2026-11-30.csv"',
            );

        $content =
            $response
                ->getContent();

        $this->assertStringContainsString(
            'Historical Product',
            $content,
        );

        $this->assertStringContainsString(
            'OLD-SKU',
            $content,
        );
    }

    public function test_export_is_not_limited_by_per_page(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 12;
            $index++
        ) {
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'order_number' => sprintf(
                        'EXPORT-%02d',
                        $index,
                    ),

                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);
        }

        $response =
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

                            'per_page' => 10,
                        ],
                    ),
                );

        $content =
            $response
                ->assertOk()
                ->getContent();

        $this->assertStringContainsString(
            'EXPORT-01',
            $content,
        );

        $this->assertStringContainsString(
            'EXPORT-12',
            $content,
        );
    }
}
