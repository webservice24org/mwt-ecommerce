<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardTrendQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminDashboardTrendQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminDashboardTrendQuery::class,
            );
    }

    public function test_daily_trend_returns_one_point_per_day_in_selected_range(): void
    {
        $customer =
            User::factory()
                ->create();

        $firstOrder =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-01 10:00:00',

                grandTotal: 10_000,
            );

        $secondOrder =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-03 12:00:00',

                grandTotal: 20_000,
            );

        $this->createItem(
            order: $firstOrder,

            quantity: 2,
        );

        $this->createItem(
            order: $secondOrder,

            quantity: 3,
        );

        $range =
            $this->rangeFactory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-03',

                timezone: 'Asia/Dhaka',
            );

        $points =
            $this->query->get(
                $range,
            );

        $this->assertCount(
            3,
            $points,
        );

        $this->assertSame(
            '2026-11-01',
            $points[0]->date,
        );

        $this->assertSame(
            1,
            $points[0]->ordersCount,
        );

        $this->assertSame(
            10_000,
            $points[0]->netSales,
        );

        $this->assertSame(
            2,
            $points[0]->productsSold,
        );

        $this->assertSame(
            '2026-11-02',
            $points[1]->date,
        );

        $this->assertSame(
            0,
            $points[1]->ordersCount,
        );

        $this->assertSame(
            0,
            $points[1]->netSales,
        );

        $this->assertSame(
            0,
            $points[1]->productsSold,
        );

        $this->assertSame(
            '2026-11-03',
            $points[2]->date,
        );

        $this->assertSame(
            1,
            $points[2]->ordersCount,
        );

        $this->assertSame(
            20_000,
            $points[2]->netSales,
        );

        $this->assertSame(
            3,
            $points[2]->productsSold,
        );
    }

    public function test_multiple_orders_on_same_day_are_aggregated(): void
    {
        $customer =
            User::factory()
                ->create();

        $first =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-05 09:00:00',

                grandTotal: 10_000,
            );

        $second =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-05 18:00:00',

                grandTotal: 15_000,
            );

        $this->createItem(
            order: $first,

            quantity: 1,
        );

        $this->createItem(
            order: $second,

            quantity: 4,
        );

        $range =
            $this->rangeFactory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-05',

                to: '2026-11-05',

                timezone: 'Asia/Dhaka',
            );

        $points =
            $this->query->get(
                $range,
            );

        $this->assertCount(
            1,
            $points,
        );

        $this->assertSame(
            2,
            $points[0]->ordersCount,
        );

        $this->assertSame(
            25_000,
            $points[0]->netSales,
        );

        $this->assertSame(
            5,
            $points[0]->productsSold,
        );
    }

    public function test_cancelled_orders_are_excluded_from_trend(): void
    {
        $customer =
            User::factory()
                ->create();

        $active =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-07 10:00:00',

                grandTotal: 10_000,
            );

        $cancelled =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-07 11:00:00',

                grandTotal: 99_000,

                status: OrderStatus::Cancelled,
            );

        $this->createItem(
            order: $active,

            quantity: 2,
        );

        $this->createItem(
            order: $cancelled,

            quantity: 50,
        );

        $range =
            $this->rangeFactory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-07',

                to: '2026-11-07',

                timezone: 'Asia/Dhaka',
            );

        $points =
            $this->query->get(
                $range,
            );

        $this->assertSame(
            1,
            $points[0]->ordersCount,
        );

        $this->assertSame(
            10_000,
            $points[0]->netSales,
        );

        $this->assertSame(
            2,
            $points[0]->productsSold,
        );
    }

    public function test_orders_outside_selected_range_are_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            placedAt: '2026-10-31 23:59:59',

            grandTotal: 10_000,
        );

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-01 00:00:00',

            grandTotal: 20_000,
        );

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-02 00:00:00',

            grandTotal: 30_000,
        );

        $range =
            $this->rangeFactory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-01',

                timezone: 'Asia/Dhaka',
            );

        $points =
            $this->query->get(
                $range,
            );

        $this->assertCount(
            1,
            $points,
        );

        $this->assertSame(
            1,
            $points[0]->ordersCount,
        );

        $this->assertSame(
            20_000,
            $points[0]->netSales,
        );
    }

    public function test_empty_range_is_zero_filled(): void
    {
        $range =
            $this->rangeFactory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-03',

                timezone: 'Asia/Dhaka',
            );

        $points =
            $this->query->get(
                $range,
            );

        $this->assertCount(
            3,
            $points,
        );

        foreach (
            $points as $point
        ) {
            $this->assertSame(
                0,
                $point->ordersCount,
            );

            $this->assertSame(
                0,
                $point->netSales,
            );

            $this->assertSame(
                0,
                $point->productsSold,
            );
        }
    }

    public function test_trend_point_array_contract_is_stable(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-10 10:00:00',

                grandTotal: 12_500,
            );

        $this->createItem(
            order: $order,

            quantity: 3,
        );

        $range =
            $this->rangeFactory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-10',

                to: '2026-11-10',

                timezone: 'Asia/Dhaka',
            );

        $point =
            $this
                ->query
                ->get(
                    $range,
                )[0];

        $this->assertSame(
            [
                'date' => '2026-11-10',

                'orders_count' => 1,

                'net_sales' => 12_500,

                'products_sold' => 3,
            ],
            $point->toArray(),
        );
    }

    private function createOrder(
        User $user,
        string $placedAt,
        int $grandTotal,
        OrderStatus $status =
        OrderStatus::Processing,
    ): Order {
        return Order::factory()
            ->for(
                $user,
            )
            ->create([
                'status' => $status,

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

                'line_total' => 1_000
                    * $quantity,
            ]);
    }
}
