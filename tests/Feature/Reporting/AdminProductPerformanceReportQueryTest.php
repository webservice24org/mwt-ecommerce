<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminProductPerformanceReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminProductPerformanceReportQueryTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminProductPerformanceReportQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminProductPerformanceReportQuery::class,
            );
    }

    public function test_same_product_and_sku_aggregate_across_orders(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $first =
            $this->createOrder(
                $customer,
                '2026-11-01 10:00:00',
            );

        $second =
            $this->createOrder(
                $customer,
                '2026-11-02 10:00:00',
            );

        $this->createItem(
            order: $first,

            product: $product,

            name: 'Product A',

            sku: 'SKU-A',

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $second,

            product: $product,

            name: 'Product A',

            sku: 'SKU-A',

            quantity: 3,

            lineTotal: 30_000,
        );

        $result =
            $this->query->get(
                $this->filters(),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            5,
            $result->rows[0]
                ->quantitySold,
        );

        $this->assertSame(
            2,
            $result->rows[0]
                ->ordersCount,
        );

        $this->assertSame(
            50_000,
            $result->rows[0]
                ->grossSales,
        );
    }

    public function test_different_skus_are_separate_performance_rows(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                $customer,
                '2026-11-01 10:00:00',
            );

        $this->createItem(
            order: $order,

            product: $product,

            name: 'T Shirt',

            sku: 'TS-S',

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $order,

            product: $product,

            name: 'T Shirt',

            sku: 'TS-L',

            quantity: 3,

            lineTotal: 30_000,
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
            2,
            $result->productGroupsCount,
        );
    }

    public function test_deleted_product_snapshot_remains_reportable(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                $customer,
                '2026-11-10 10:00:00',
            );

        $item =
            $this->createItem(
                order: $order,

                product: $product,

                name: 'Historical Product',

                sku: 'OLD-001',

                quantity: 4,

                lineTotal: 40_000,
            );

        $product->delete();

        $item->refresh();

        $this->assertNull(
            $item->product_id,
        );

        $result =
            $this->query->get(
                $this->filters(),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertNull(
            $result->rows[0]
                ->productId,
        );

        $this->assertSame(
            'Historical Product',
            $result->rows[0]
                ->productName,
        );

        $this->assertSame(
            'OLD-001',
            $result->rows[0]
                ->sku,
        );
    }

    public function test_search_matches_product_name(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                $customer,
                '2026-11-01 10:00:00',
            );

        $first =
            Product::factory()
                ->create();

        $second =
            Product::factory()
                ->create();

        $this->createItem(
            $order,
            $first,
            'Red Shirt',
            'RED-01',
            2,
            20_000,
        );

        $this->createItem(
            $order,
            $second,
            'Blue Shoe',
            'BLUE-01',
            1,
            10_000,
        );

        $result =
            $this->query->get(
                $this->filters(
                    search: 'Red',
                ),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'Red Shirt',
            $result->rows[0]
                ->productName,
        );
    }

    public function test_search_matches_sku(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                $customer,
                '2026-11-01 10:00:00',
            );

        $this->createItem(
            $order,
            $product,
            'Product',
            'SPECIAL-999',
            1,
            10_000,
        );

        $result =
            $this->query->get(
                $this->filters(
                    search: '999',
                ),
            );

        $this->assertCount(
            1,
            $result->rows,
        );
    }

    public function test_order_status_filter_is_applied(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $active =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-01 10:00:00',

                status: OrderStatus::Completed,
            );

        $cancelled =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-02 10:00:00',

                status: OrderStatus::Cancelled,
            );

        $this->createItem(
            $active,
            $product,
            'Product',
            'SKU',
            2,
            20_000,
        );

        $this->createItem(
            $cancelled,
            $product,
            'Product',
            'SKU',
            50,
            500_000,
        );

        $result =
            $this->query->get(
                $this->filters(
                    statuses: [
                        OrderStatus::Completed,
                    ],
                ),
            );

        $this->assertSame(
            2,
            $result->quantitySold,
        );

        $this->assertSame(
            20_000,
            $result->grossSales,
        );
    }

    public function test_no_status_filter_includes_cancelled_order_items(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $cancelled =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-01 10:00:00',

                status: OrderStatus::Cancelled,
            );

        $this->createItem(
            $cancelled,
            $product,
            'Product',
            'SKU',
            3,
            30_000,
        );

        $result =
            $this->query->get(
                $this->filters(),
            );

        $this->assertSame(
            3,
            $result->quantitySold,
        );
    }

    public function test_payment_status_filter_is_applied(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $paid =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-01 10:00:00',

                paymentStatus: PaymentStatus::Paid,
            );

        $due =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-02 10:00:00',

                paymentStatus: PaymentStatus::Due,
            );

        $this->createItem(
            $paid,
            $product,
            'Product',
            'SKU',
            2,
            20_000,
        );

        $this->createItem(
            $due,
            $product,
            'Product',
            'SKU',
            5,
            50_000,
        );

        $result =
            $this->query->get(
                $this->filters(
                    paymentStatuses: [
                        PaymentStatus::Paid,
                    ],
                ),
            );

        $this->assertSame(
            2,
            $result->quantitySold,
        );
    }

    public function test_summary_is_for_entire_filtered_result_not_current_page(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                $customer,
                '2026-11-01 10:00:00',
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
            12,
            $result->productGroupsCount,
        );

        $this->assertSame(
            12,
            $result->quantitySold,
        );

        $this->assertSame(
            12_000,
            $result->grossSales,
        );

        /*
         * One order contained all 12 products.
         */
        $this->assertSame(
            1,
            $result->ordersCount,
        );
    }

    public function test_report_respects_half_open_date_range(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $outside =
            $this->createOrder(
                $customer,
                '2026-10-31 23:59:59',
            );

        $inside =
            $this->createOrder(
                $customer,
                '2026-11-01 00:00:00',
            );

        $after =
            $this->createOrder(
                $customer,
                '2026-11-02 00:00:00',
            );

        $this->createItem(
            $outside,
            $product,
            'Product',
            'SKU',
            10,
            100_000,
        );

        $this->createItem(
            $inside,
            $product,
            'Product',
            'SKU',
            2,
            20_000,
        );

        $this->createItem(
            $after,
            $product,
            'Product',
            'SKU',
            20,
            200_000,
        );

        $filters =
            new ProductPerformanceReportFiltersData(
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

        $this->assertSame(
            2,
            $result->quantitySold,
        );
    }

    public function test_row_and_result_array_contracts_are_stable(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                $customer,
                '2026-11-01 10:00:00',
            );

        $this->createItem(
            $order,
            $product,
            'Contract Product',
            'CONTRACT-1',
            2,
            12_345,
        );

        $payload =
            $this
                ->query
                ->get(
                    $this->filters(),
                )
                ->toArray();

        $this->assertSame(
            [
                'data',
                'meta',
                'summary',
            ],
            array_keys(
                $payload,
            ),
        );

        $this->assertSame(
            [
                'product_id',
                'product_name',
                'sku',
                'quantity_sold',
                'orders_count',
                'gross_sales',
            ],
            array_keys(
                $payload['data'][0],
            ),
        );

        $this->assertSame(
            12_345,
            $payload['data'][0][
                'gross_sales'
            ],
        );
    }

    /**
     * @param  list<OrderStatus>  $statuses
     * @param  list<PaymentStatus>  $paymentStatuses
     */
    private function filters(
        array $statuses = [],
        array $paymentStatuses = [],
        ?string $search = null,
        int $perPage = 25,
    ): ProductPerformanceReportFiltersData {
        return new ProductPerformanceReportFiltersData(
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
