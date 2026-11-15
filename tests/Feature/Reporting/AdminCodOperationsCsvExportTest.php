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

final class AdminCodOperationsCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_cod_operations_csv(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.cod.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_export_cod_operations_csv(): void
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
                    'name' => 'COD Export Customer',
                ]);

        $this->createOrder(
            customer: $customer,

            number: 'COD-CSV-001',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 25_000,

            placedAt: '2026-11-10 10:00:00',
        );

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
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

        $csv =
            $response
                ->streamedContent();

        $this->assertStringContainsString(
            'Order Number',
            $csv,
        );

        $this->assertStringContainsString(
            'COD-CSV-001',
            $csv,
        );

        $this->assertStringContainsString(
            'COD Export Customer',
            $csv,
        );

        $this->assertStringContainsString(
            '250.00',
            $csv,
        );

        $this->assertStringContainsString(
            'unpaid_stale',
            $csv,
        );

        $this->assertStringContainsString(
            'high',
            $csv,
        );

        $this->assertStringContainsString(
            'Yes',
            $csv,
        );

        CarbonImmutable::setTestNow();
    }

    public function test_csv_respects_payment_and_fulfilment_filters(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-CSV-MATCH',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 15_000,
        );

        $this->createOrder(
            customer: $customer,

            number: 'COD-CSV-OTHER',

            status: OrderStatus::Completed,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 20_000,
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
                        [
                            'payment_status' => [
                                PaymentStatus::Due->value,
                            ],

                            'fulfilment_status' => [
                                FulfilmentStatus::Delivered->value,
                            ],
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'COD-CSV-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'COD-CSV-OTHER',
            $csv,
        );
    }

    public function test_csv_respects_search_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Search Export Customer',
                ]);

        $this->createOrder(
            customer: $customer,

            number: 'COD-SEARCH-EXPORT',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,
        );

        $this->createOrder(
            customer: $customer,

            number: 'COD-NOT-MATCHED',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
                        [
                            'search' => 'COD-SEARCH-EXPORT',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'COD-SEARCH-EXPORT',
            $csv,
        );

        $this->assertStringNotContainsString(
            'COD-NOT-MATCHED',
            $csv,
        );
    }

    public function test_paid_order_exports_zero_amount_due(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-CSV-PAID',

            status: OrderStatus::Completed,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 30_000,
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
                        [
                            'search' => 'COD-CSV-PAID',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            '300.00',
            $csv,
        );

        $this->assertStringContainsString(
            ',0.00,',
            $csv,
        );

        $this->assertStringContainsString(
            'none',
            $csv,
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
                    'name' => '=HYPERLINK("malicious")',
                ]);

        $this->createOrder(
            customer: $customer,

            number: '+COD-FORMULA',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            "'+COD-FORMULA",
            $csv,
        );

        $this->assertStringContainsString(
            '\'=HYPERLINK',
            $csv,
        );
    }

    public function test_csv_contains_collection_risk_columns(): void
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
                        'admin.reports.operations.cod.csv',
                    ),
                );

        $csv =
            $response
                ->streamedContent();

        $this->assertStringContainsString(
            'Collection Risk',
            $csv,
        );

        $this->assertStringContainsString(
            'Risk Severity',
            $csv,
        );

        $this->assertStringContainsString(
            'Risk Priority',
            $csv,
        );

        $this->assertStringContainsString(
            'Follow-up Required',
            $csv,
        );
    }

    private function createOrder(
        User $customer,
        string $number,
        OrderStatus $status,
        PaymentStatus $paymentStatus,
        FulfilmentStatus $fulfilmentStatus,
        int $grandTotal,
        string $placedAt =
        '2026-11-15 10:00:00',
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => $number,

                'status' => $status,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => $paymentStatus,

                'fulfilment_status' => $fulfilmentStatus,

                'currency_code' => 'BDT',

                'subtotal' => $grandTotal,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => $grandTotal,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }
}
