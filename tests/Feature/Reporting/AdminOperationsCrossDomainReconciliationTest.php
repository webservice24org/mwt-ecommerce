<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Data\CodOperationsReportFiltersData;
use App\Domain\Reporting\Data\InventoryOperationsReportFiltersData;
use App\Domain\Reporting\Data\OperationalOrdersReportFiltersData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminActionQueueQuery;
use App\Domain\Reporting\Queries\GetAdminCodOperationsReportQuery;
use App\Domain\Reporting\Queries\GetAdminInventoryOperationsReportQuery;
use App\Domain\Reporting\Queries\GetAdminOperationalOrdersReportQuery;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminOperationsCrossDomainReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_operational_and_cod_reports_reconcile_same_unpaid_cod_order(): void
    {
        $this->freezeTime();

        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                number: 'CROSS-COD-UNPAID',

                status: OrderStatus::Processing,

                paymentStatus: PaymentStatus::Unpaid,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,

                grandTotal: 25_000,

                placedAt: '2026-11-10 10:00:00',
            );

        $operational =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                new OperationalOrdersReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,

                    perPage: 100,
                ),
            );

        $cod =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                new CodOperationsReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,

                    perPage: 100,
                ),
            );

        $this->assertCount(
            1,
            $operational->rows,
        );

        $this->assertCount(
            1,
            $cod->rows,
        );

        $operationalRow =
            $operational->rows[0];

        $codRow =
            $cod->rows[0];

        $this->assertSame(
            $order->id,
            $operationalRow->orderId,
        );

        $this->assertSame(
            $order->id,
            $codRow->orderId,
        );

        $this->assertSame(
            $operationalRow->orderNumber,
            $codRow->orderNumber,
        );

        $this->assertSame(
            $operationalRow->status,
            $codRow->status,
        );

        $this->assertSame(
            $operationalRow->paymentStatus,
            $codRow->paymentStatus,
        );

        $this->assertSame(
            $operationalRow->fulfilmentStatus,
            $codRow->fulfilmentStatus,
        );

        $this->assertSame(
            $operationalRow->currencyCode,
            $codRow->currencyCode,
        );

        $this->assertSame(
            25_000,
            $operationalRow->grandTotal,
        );

        $this->assertSame(
            25_000,
            $codRow->grandTotal,
        );

        $this->assertSame(
            $operationalRow->grandTotal,
            $codRow->grandTotal,
        );

        $this->assertSame(
            25_000,
            $operationalRow->amountDue,
        );

        $this->assertSame(
            25_000,
            $codRow->amountDue,
        );

        $this->assertSame(
            $operationalRow->amountDue,
            $codRow->amountDue,
        );

        $this->assertSame(
            PaymentMethod::CashOnDelivery->value,
            $codRow->paymentMethod,
        );
    }

    public function test_operational_and_cod_reports_reconcile_paid_amount_due_as_zero(): void
    {
        $this->freezeTime();

        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                number: 'CROSS-COD-PAID',

                status: OrderStatus::Completed,

                paymentStatus: PaymentStatus::Paid,

                fulfilmentStatus: FulfilmentStatus::Delivered,

                grandTotal: 30_000,
            );

        $operational =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                new OperationalOrdersReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,

                    perPage: 100,
                ),
            );

        $cod =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                new CodOperationsReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,

                    perPage: 100,
                ),
            );

        $this->assertCount(
            1,
            $operational->rows,
        );

        $this->assertCount(
            1,
            $cod->rows,
        );

        $this->assertSame(
            30_000,
            $operational
                ->rows[0]
                ->grandTotal,
        );

        $this->assertSame(
            30_000,
            $cod
                ->rows[0]
                ->grandTotal,
        );

        $this->assertSame(
            0,
            $operational
                ->rows[0]
                ->amountDue,
        );

        $this->assertSame(
            0,
            $cod
                ->rows[0]
                ->amountDue,
        );

        $this->assertSame(
            $operational
                ->rows[0]
                ->amountDue,

            $cod
                ->rows[0]
                ->amountDue,
        );
    }

    public function test_operational_and_cod_ageing_use_same_authoritative_order_time(): void
    {
        $this->freezeTime();

        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                number: 'CROSS-AGE',

                status: OrderStatus::Processing,

                paymentStatus: PaymentStatus::Unpaid,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,

                grandTotal: 12_500,

                placedAt: '2026-11-10 10:00:00',
            );

        $operational =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                new OperationalOrdersReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,
                ),
            );

        $cod =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                new CodOperationsReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,
                ),
            );

        $this->assertSame(
            $operational
                ->rows[0]
                ->ageHours,

            $cod
                ->rows[0]
                ->ageHours,
        );

        $this->assertSame(
            $operational
                ->rows[0]
                ->ageDays,

            $cod
                ->rows[0]
                ->ageDays,
        );

        $this->assertSame(
            $operational
                ->rows[0]
                ->placedAt,

            $cod
                ->rows[0]
                ->placedAt,
        );
    }

    public function test_cancelled_orders_are_not_silently_removed_from_operational_or_cod_reports(): void
    {
        $this->freezeTime();

        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                number: 'CROSS-CANCELLED',

                status: OrderStatus::Cancelled,

                paymentStatus: PaymentStatus::Unpaid,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,

                grandTotal: 8_000,
            );

        $operational =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                new OperationalOrdersReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,
                ),
            );

        $cod =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                new CodOperationsReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,
                ),
            );

        $this->assertCount(
            1,
            $operational->rows,
        );

        $this->assertCount(
            1,
            $cod->rows,
        );

        $this->assertSame(
            OrderStatus::Cancelled->value,
            $operational
                ->rows[0]
                ->status,
        );

        $this->assertSame(
            OrderStatus::Cancelled->value,
            $cod
                ->rows[0]
                ->status,
        );
    }

    public function test_inventory_negative_stock_and_out_of_stock_remain_distinct(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $negative =
            Product::factory()
                ->published()
                ->create([
                    'name' => 'Cross Negative Stock',

                    'sku' => 'CROSS-NEGATIVE',

                    'track_inventory' => true,

                    'stock_quantity' => -3,
                ]);

        $outOfStock =
            Product::factory()
                ->published()
                ->create([
                    'name' => 'Cross Out Of Stock',

                    'sku' => 'CROSS-ZERO',

                    'track_inventory' => true,

                    'stock_quantity' => 0,
                ]);

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData(
                    stockStates: [
                        'negative_stock',
                        'out_of_stock',
                    ],

                    perPage: 100,
                ),
            );

        $negativeRow =
            $this->inventoryRowByProductId(
                $result->rows,
                (int) $negative->id,
            );

        $zeroRow =
            $this->inventoryRowByProductId(
                $result->rows,
                (int) $outOfStock->id,
            );

        $this->assertSame(
            'negative_stock',
            $negativeRow->stockState,
        );

        $this->assertSame(
            'negative_stock',
            $negativeRow
                ->alert
                ->state
                ->value,
        );

        $this->assertTrue(
            $negativeRow
                ->alert
                ->requiresAttention(),
        );

        $this->assertSame(
            'out_of_stock',
            $zeroRow->stockState,
        );

        $this->assertSame(
            'out_of_stock',
            $zeroRow
                ->alert
                ->state
                ->value,
        );

        $this->assertTrue(
            $zeroRow
                ->alert
                ->requiresAttention(),
        );

        $this->assertNotSame(
            $negativeRow->stockState,
            $zeroRow->stockState,
        );
    }

    public function test_inventory_untracked_product_is_not_reported_as_out_of_stock(): void
    {
        $product =
            Product::factory()
                ->published()
                ->create([
                    'name' => 'Cross Untracked',

                    'sku' => 'CROSS-UNTRACKED',

                    'track_inventory' => false,

                    'stock_quantity' => 0,
                ]);

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData(
                    search: 'CROSS-UNTRACKED',

                    perPage: 100,
                ),
            );

        $this->assertCount(
            1,
            $result->rows,
        );

        $row =
            $result->rows[0];

        $this->assertSame(
            $product->id,
            $row->productId,
        );

        $this->assertFalse(
            $row->trackInventory,
        );

        $this->assertSame(
            'not_tracked',
            $row->stockState,
        );

        $this->assertSame(
            'not_tracked',
            $row
                ->alert
                ->state
                ->value,
        );

        $this->assertFalse(
            $row
                ->alert
                ->requiresAttention(),
        );
    }

    public function test_action_queue_reconciles_operational_cod_and_inventory_authorities(): void
    {
        $this->freezeTime();

        config([
            'reporting.operations.pending_age_hours' => 24,

            'reporting.operations.processing_age_hours' => 24,

            'reporting.operations.unfulfilled_age_hours' => 24,

            'reporting.cod_operations.unpaid_stale_hours' => 48,

            'reporting.cod_operations.due_grace_hours' => 24,

            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,

            'reporting.action_queue.lookback_days' => 90,

            'reporting.action_queue.provider_page_size' => 100,

            'reporting.action_queue.provider_max_rows' => 10_000,
        ]);

        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                number: 'CROSS-QUEUE-ORDER',

                status: OrderStatus::Pending,

                paymentStatus: PaymentStatus::Unpaid,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,

                grandTotal: 18_000,

                placedAt: '2026-11-10 10:00:00',
            );

        $product =
            Product::factory()
                ->published()
                ->create([
                    'name' => 'Cross Queue Inventory',

                    'sku' => 'CROSS-QUEUE-SKU',

                    'track_inventory' => true,

                    'stock_quantity' => -2,
                ]);

        $operational =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                new OperationalOrdersReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,

                    perPage: 100,
                ),
            );

        $cod =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                new CodOperationsReportFiltersData(
                    range: $this->range(),

                    search: $order->order_number,

                    perPage: 100,
                ),
            );

        $inventory =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData(
                    search: 'CROSS-QUEUE-SKU',

                    perPage: 100,
                ),
            );

        $this->assertTrue(
            $operational
                ->rows[0]
                ->attention
                ->requiresAttention(),
        );

        $this->assertTrue(
            $cod
                ->rows[0]
                ->collectionRisk
                ->requiresFollowUp(),
        );

        $this->assertTrue(
            $inventory
                ->rows[0]
                ->alert
                ->requiresAttention(),
        );

        $queue =
            app(
                GetAdminActionQueueQuery::class,
            )->get(
                filters: new ActionQueueFiltersData(
                    requiresAction: true,

                    perPage: 100,
                ),

                page: 1,
            );

        $operationalQueueItem =
            $this->actionQueueItem(
                items: $queue->items,

                source: ActionQueueSource::OperationalOrders,

                entityId: (int) $order->id,
            );

        $codQueueItem =
            $this->actionQueueItem(
                items: $queue->items,

                source: ActionQueueSource::CodOperations,

                entityId: (int) $order->id,
            );

        $inventoryQueueItem =
            $this->actionQueueItem(
                items: $queue->items,

                source: ActionQueueSource::InventoryOperations,

                entityId: (int) $product->id,
            );

        $this->assertSame(
            $order->order_number,
            $operationalQueueItem->reference,
        );

        $this->assertSame(
            $order->order_number,
            $codQueueItem->reference,
        );

        $this->assertSame(
            $operational
                ->rows[0]
                ->attention
                ->state
                ->severity(),

            $operationalQueueItem
                ->severity
                ->value,
        );

        $this->assertSame(
            $cod
                ->rows[0]
                ->collectionRisk
                ->state
                ->severity(),

            $codQueueItem
                ->severity
                ->value,
        );

        $this->assertSame(
            $inventory
                ->rows[0]
                ->alert
                ->state
                ->severity(),

            $inventoryQueueItem
                ->severity
                ->value,
        );

        $this->assertTrue(
            $operationalQueueItem->requiresAction,
        );

        $this->assertTrue(
            $codQueueItem->requiresAction,
        );

        $this->assertTrue(
            $inventoryQueueItem->requiresAction,
        );
    }

    public function test_filtered_operational_report_and_csv_use_same_order_universe(): void
    {
        $this->freezeTime();

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $matching =
            $this->createOrder(
                customer: $customer,

                number: 'CROSS-EXPORT-MATCH',

                status: OrderStatus::Processing,

                paymentStatus: PaymentStatus::Due,

                fulfilmentStatus: FulfilmentStatus::Delivered,

                grandTotal: 15_000,
            );

        $this->createOrder(
            customer: $customer,

            number: 'CROSS-EXPORT-OTHER',

            status: OrderStatus::Completed,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 30_000,
        );

        $filters =
            new OperationalOrdersReportFiltersData(
                range: $this->range(),

                statuses: [
                    OrderStatus::Processing,
                ],

                paymentStatuses: [
                    PaymentStatus::Due,
                ],

                search: 'CROSS-EXPORT',

                perPage: 100,
            );

        $report =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $filters,
            );

        $this->assertCount(
            1,
            $report->rows,
        );

        $this->assertSame(
            $matching->id,
            $report
                ->rows[0]
                ->orderId,
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.orders.csv',
                        [
                            'preset' => ReportDatePreset::Custom->value,

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',

                            'status' => [
                                OrderStatus::Processing->value,
                            ],

                            'payment_status' => [
                                PaymentStatus::Due->value,
                            ],

                            'search' => 'CROSS-EXPORT',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'CROSS-EXPORT-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'CROSS-EXPORT-OTHER',
            $csv,
        );

        /*
         * Money remains authoritative integer minor units.
         */
        $this->assertStringContainsString(
            ',15000,15000,BDT,',
            $csv,
        );
    }

    public function test_filtered_cod_report_and_csv_use_same_order_universe(): void
    {
        $this->freezeTime();

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $matching =
            $this->createOrder(
                customer: $customer,

                number: 'CROSS-COD-EXPORT-MATCH',

                status: OrderStatus::Processing,

                paymentStatus: PaymentStatus::Due,

                fulfilmentStatus: FulfilmentStatus::Delivered,

                grandTotal: 14_000,
            );

        $this->createOrder(
            customer: $customer,

            number: 'CROSS-COD-EXPORT-OTHER',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 20_000,
        );

        $report =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                new CodOperationsReportFiltersData(
                    range: $this->range(),

                    statuses: [
                        OrderStatus::Processing,
                    ],

                    fulfilmentStatuses: [
                        FulfilmentStatus::Delivered,
                    ],

                    paymentStatuses: [
                        PaymentStatus::Due,
                    ],

                    search: 'CROSS-COD-EXPORT',

                    perPage: 100,
                ),
            );

        $this->assertCount(
            1,
            $report->rows,
        );

        $this->assertSame(
            $matching->id,
            $report
                ->rows[0]
                ->orderId,
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
                            'preset' => ReportDatePreset::Custom->value,

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',

                            'status' => [
                                OrderStatus::Processing->value,
                            ],

                            'payment_status' => [
                                PaymentStatus::Due->value,
                            ],

                            'fulfilment_status' => [
                                FulfilmentStatus::Delivered->value,
                            ],

                            'search' => 'CROSS-COD-EXPORT',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'CROSS-COD-EXPORT-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'CROSS-COD-EXPORT-OTHER',
            $csv,
        );

        $this->assertStringContainsString(
            ',14000,14000,BDT,',
            $csv,
        );
    }

    /**
     * @param  array<int, mixed>  $rows
     */
    private function inventoryRowByProductId(
        array $rows,
        int $productId,
    ): mixed {
        foreach (
            $rows as $row
        ) {
            if (
                $row->productId ===
                $productId
            ) {
                return $row;
            }
        }

        $this->fail(
            sprintf(
                'Inventory row for product [%d] was not found.',
                $productId,
            ),
        );
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function actionQueueItem(
        array $items,
        ActionQueueSource $source,
        int $entityId,
    ): mixed {
        foreach (
            $items as $item
        ) {
            if (
                $item->source ===
                $source &&
                $item->entityId ===
                $entityId
            ) {
                return $item;
            }
        }

        $this->fail(
            sprintf(
                'Action Queue item [%s:%d] was not found.',
                $source->value,
                $entityId,
            ),
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
        User $customer,
        string $number,
        OrderStatus $status,
        PaymentStatus $paymentStatus,
        FulfilmentStatus $fulfilmentStatus,
        int $grandTotal,
        string $placedAt = '2026-11-15 10:00:00',
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
