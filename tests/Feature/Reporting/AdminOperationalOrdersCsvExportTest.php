<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminOperationalOrdersCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_operational_orders(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.orders.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_export_operational_orders_csv(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Export Customer',
                ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'OPS-CSV-001',

                'status' => OrderStatus::Pending,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,

                'currency_code' => 'BDT',

                'subtotal' => 25_000,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 25_000,

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
                        'admin.reports.operations.orders.csv',
                        [
                            'preset' => 'custom',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );

        $content =
            $response
                ->streamedContent();

        $this->assertStringContainsString(
            'Order Number',
            $content,
        );

        $this->assertStringContainsString(
            'OPS-CSV-001',
            $content,
        );

        $this->assertStringContainsString(
            'Export Customer',
            $content,
        );

        $this->assertStringContainsString(
            '250.00',
            $content,
        );

        $this->assertStringContainsString(
            'pending_too_long',
            $content,
        );

        CarbonImmutable::setTestNow();
    }

    public function test_export_respects_operational_filters(): void
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
                'order_number' => 'OPS-CSV-MATCH',

                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Due,

                'fulfilment_status' => FulfilmentStatus::Processing,

                'currency_code' => 'BDT',

                'subtotal' => 15_000,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 15_000,

                'placed_at' => now(),
            ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'OPS-CSV-OTHER',

                'status' => OrderStatus::Completed,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Paid,

                'fulfilment_status' => FulfilmentStatus::Delivered,

                'currency_code' => 'BDT',

                'subtotal' => 20_000,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 20_000,

                'placed_at' => now(),
            ]);

        $content =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.orders.csv',
                        [
                            'status' => [
                                OrderStatus::Processing->value,
                            ],

                            'payment_status' => [
                                PaymentStatus::Due->value,
                            ],
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'OPS-CSV-MATCH',
            $content,
        );

        $this->assertStringNotContainsString(
            'OPS-CSV-OTHER',
            $content,
        );
    }

    public function test_csv_escapes_spreadsheet_formula_prefixes(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => '=HYPERLINK("bad")',
                ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => '+OPS-FORMULA',

                'status' => OrderStatus::Pending,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,

                'currency_code' => 'BDT',

                'subtotal' => 10_000,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 10_000,

                'placed_at' => now(),
            ]);

        $content =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.orders.csv',
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            "'+OPS-FORMULA",
            $content,
        );

        $this->assertStringContainsString(
            '\'=HYPERLINK',
            $content,
        );
    }
}
