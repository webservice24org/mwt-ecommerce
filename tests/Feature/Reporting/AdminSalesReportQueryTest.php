<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminSalesReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminSalesReportQueryTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminSalesReportQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminSalesReportQuery::class,
            );
    }

    public function test_report_returns_orders_in_newest_first_order(): void
    {
        $customer =
            User::factory()
                ->create();

        $older =
            $this->createOrder(
                customer: $customer,

                orderNumber: 'ORD-OLD',

                placedAt: '2026-11-01 10:00:00',
            );

        $newer =
            $this->createOrder(
                customer: $customer,

                orderNumber: 'ORD-NEW',

                placedAt: '2026-11-10 10:00:00',
            );

        $result =
            $this->query->get(
                $this->filters(),
            );

        $this->assertCount(
            2,
            $result->rows,
        );

        $this->assertSame(
            $newer->id,
            $result->rows[0]->id,
        );

        $this->assertSame(
            $older->id,
            $result->rows[1]->id,
        );
    }

    public function test_report_filters_by_order_status(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-PROCESSING',

            placedAt: '2026-11-01 10:00:00',

            status: OrderStatus::Processing,
        );

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-CANCELLED',

            placedAt: '2026-11-02 10:00:00',

            status: OrderStatus::Cancelled,
        );

        $result =
            $this->query->get(
                $this->filters(
                    statuses: [
                        OrderStatus::Cancelled,
                    ],
                ),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'ORD-CANCELLED',
            $result->rows[0]->orderNumber,
        );
    }

    public function test_report_filters_by_payment_status(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-PAID',

            placedAt: '2026-11-01 10:00:00',

            paymentStatus: PaymentStatus::Paid,
        );

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-DUE',

            placedAt: '2026-11-02 10:00:00',

            paymentStatus: PaymentStatus::Due,
        );

        $result =
            $this->query->get(
                $this->filters(
                    paymentStatuses: [
                        PaymentStatus::Due,
                    ],
                ),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'ORD-DUE',
            $result->rows[0]->orderNumber,
        );
    }

    public function test_report_searches_order_number(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-ABC-001',

            placedAt: '2026-11-01 10:00:00',
        );

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-XYZ-002',

            placedAt: '2026-11-02 10:00:00',
        );

        $result =
            $this->query->get(
                $this->filters(
                    search: 'ABC',
                ),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'ORD-ABC-001',
            $result->rows[0]->orderNumber,
        );
    }

    public function test_report_searches_customer_name(): void
    {
        $mohiuddin =
            User::factory()
                ->create([
                    'name' => 'Mohiuddin',
                ]);

        $other =
            User::factory()
                ->create([
                    'name' => 'Other Customer',
                ]);

        $this->createOrder(
            customer: $mohiuddin,

            orderNumber: 'ORD-001',

            placedAt: '2026-11-01 10:00:00',
        );

        $this->createOrder(
            customer: $other,

            orderNumber: 'ORD-002',

            placedAt: '2026-11-02 10:00:00',
        );

        $result =
            $this->query->get(
                $this->filters(
                    search: 'Mohiuddin',
                ),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'Mohiuddin',
            $result->rows[0]->customerName,
        );
    }

    public function test_summary_reconciles_filtered_order_totals(): void
    {
        $customer =
            User::factory()
                ->create();

        $first =
            $this->createOrder(
                customer: $customer,

                orderNumber: 'ORD-001',

                placedAt: '2026-11-01 10:00:00',

                subtotal: 10_000,

                discountTotal: 1_000,

                deliveryTotal: 500,

                taxTotal: 200,

                grandTotal: 9_700,
            );

        $second =
            $this->createOrder(
                customer: $customer,

                orderNumber: 'ORD-002',

                placedAt: '2026-11-02 10:00:00',

                subtotal: 20_000,

                discountTotal: 2_000,

                deliveryTotal: 800,

                taxTotal: 300,

                grandTotal: 19_100,
            );

        $this->createItem(
            order: $first,

            quantity: 2,
        );

        $this->createItem(
            order: $second,

            quantity: 3,
        );

        $result =
            $this->query->get(
                $this->filters(),
            );

        $this->assertSame(
            30_000,
            $result->subtotal,
        );

        $this->assertSame(
            3_000,
            $result->discountTotal,
        );

        $this->assertSame(
            1_300,
            $result->deliveryTotal,
        );

        $this->assertSame(
            500,
            $result->taxTotal,
        );

        $this->assertSame(
            28_800,
            $result->grandTotal,
        );

        $this->assertSame(
            5,
            $result->itemsQuantity,
        );
    }

    public function test_summary_is_for_entire_filtered_result_not_current_page(): void
    {
        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 12;
            $index++
        ) {
            $this->createOrder(
                customer: $customer,

                orderNumber: sprintf(
                    'ORD-%02d',
                    $index,
                ),

                placedAt: sprintf(
                    '2026-11-%02d 10:00:00',
                    $index,
                ),

                grandTotal: 1_000,
            );
        }

        $result =
            $this->query->get(
                filters: $this->filters(
                    perPage: 10,
                ),

                page: 1,
            );

        $this->assertCount(
            10,
            $result->rows,
        );

        $this->assertSame(
            12,
            $result->total,
        );

        $this->assertSame(
            12_000,
            $result->grandTotal,
        );
    }

    public function test_item_quantities_are_not_multiplied_into_financial_totals(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                orderNumber: 'ORD-001',

                placedAt: '2026-11-01 10:00:00',

                subtotal: 10_000,

                grandTotal: 10_000,
            );

        $this->createItem(
            order: $order,

            quantity: 2,
        );

        $this->createItem(
            order: $order,

            quantity: 3,
        );

        $result =
            $this->query->get(
                $this->filters(),
            );

        $this->assertSame(
            10_000,
            $result->subtotal,
        );

        $this->assertSame(
            10_000,
            $result->grandTotal,
        );

        $this->assertSame(
            5,
            $result->itemsQuantity,
        );

        $this->assertSame(
            5,
            $result->rows[0]->itemsQuantity,
        );
    }

    public function test_report_respects_half_open_date_range(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-BEFORE',

            placedAt: '2026-10-31 23:59:59',
        );

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-IN',

            placedAt: '2026-11-01 00:00:00',
        );

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-AFTER',

            placedAt: '2026-11-02 00:00:00',
        );

        $filters =
            new SalesReportFiltersData(
                range: $this
                    ->rangeFactory
                    ->make(
                        preset: ReportDatePreset::Custom,

                        from: '2026-11-01',

                        to: '2026-11-01',

                        timezone: 'Asia/Dhaka',
                    ),
            );

        $result =
            $this->query->get(
                $filters,
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'ORD-IN',
            $result->rows[0]->orderNumber,
        );
    }

    public function test_no_status_filter_includes_cancelled_orders(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-ACTIVE',

            placedAt: '2026-11-01 10:00:00',
        );

        $this->createOrder(
            customer: $customer,

            orderNumber: 'ORD-CANCELLED',

            placedAt: '2026-11-02 10:00:00',

            status: OrderStatus::Cancelled,
        );

        $result =
            $this->query->get(
                $this->filters(),
            );

        $this->assertCount(
            2,
            $result->rows,
        );
    }

    public function test_filter_contract_normalizes_search_and_per_page(): void
    {
        $filters =
            $this->filters(
                search: '   ',

                perPage: 999,
            );

        $this->assertNull(
            $filters->normalizedSearch(),
        );

        $this->assertSame(
            100,
            $filters->normalizedPerPage(),
        );
    }

    private function filters(
        array $statuses = [],
        array $paymentStatuses = [],
        ?string $search = null,
        int $perPage = 25,
    ): SalesReportFiltersData {
        return new SalesReportFiltersData(
            range: $this
                ->rangeFactory
                ->make(
                    preset: ReportDatePreset::Custom,

                    from: '2026-11-01',

                    to: '2026-11-30',

                    timezone: 'Asia/Dhaka',
                ),

            statuses: $statuses,

            paymentStatuses: $paymentStatuses,

            search: $search,

            perPage: $perPage,
        );
    }

    private function createOrder(
        User $customer,
        string $orderNumber,
        string $placedAt,
        OrderStatus $status =
        OrderStatus::Processing,
        PaymentStatus $paymentStatus =
        PaymentStatus::Unpaid,
        FulfilmentStatus $fulfilmentStatus =
        FulfilmentStatus::Processing,
        int $subtotal = 0,
        int $discountTotal = 0,
        int $deliveryTotal = 0,
        int $taxTotal = 0,
        int $grandTotal = 0,
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => $orderNumber,

                'status' => $status,

                'payment_status' => $paymentStatus,

                'fulfilment_status' => $fulfilmentStatus,

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
        int $quantity,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'unit_price' => 1_000,

                'quantity' => $quantity,

                'line_total' => $quantity
                    * 1_000,
            ]);
    }
}
