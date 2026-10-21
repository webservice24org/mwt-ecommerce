<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class OrderSchemaFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_table_has_required_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns(
                'orders',
                [
                    'id',
                    'user_id',
                    'delivery_address_id',
                    'order_number',
                    'status',
                    'payment_method',
                    'payment_status',
                    'fulfilment_status',
                    'currency_code',
                    'subtotal',
                    'discount_total',
                    'delivery_total',
                    'tax_total',
                    'grand_total',
                    'delivery_recipient_name',
                    'delivery_phone',
                    'delivery_address_line_1',
                    'delivery_address_line_2',
                    'delivery_city',
                    'delivery_state_or_region',
                    'delivery_postal_code',
                    'delivery_country_code',
                    'placed_at',
                    'created_at',
                    'updated_at',
                ],
            ),
        );
    }

    public function test_order_items_table_has_required_snapshot_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns(
                'order_items',
                [
                    'id',
                    'order_id',
                    'product_id',
                    'variant_id',
                    'product_name',
                    'sku',
                    'variant_description',
                    'unit_price',
                    'quantity',
                    'line_total',
                    'created_at',
                    'updated_at',
                ],
            ),
        );
    }

    public function test_customer_has_orders_relationship(): void
    {
        $user = User::factory()
            ->create();

        Order::factory()
            ->for($user)
            ->count(2)
            ->create();

        $this->assertCount(
            2,
            $user
                ->orders()
                ->get(),
        );
    }

    public function test_order_can_have_multiple_items(): void
    {
        $order = Order::factory()
            ->create();

        OrderItem::factory()
            ->for($order)
            ->count(2)
            ->create();

        $this->assertCount(
            2,
            $order
                ->items()
                ->get(),
        );
    }

    public function test_order_preserves_delivery_snapshot_when_saved_address_is_deleted(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->create([
                'recipient_name' => 'Original Recipient',

                'address_line_1' => '123 Original Road',

                'city' => 'Dhaka',
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'delivery_address_id' => $address->id,

                'delivery_recipient_name' => $address->recipient_name,

                'delivery_phone' => $address->phone,

                'delivery_address_line_1' => $address->address_line_1,

                'delivery_address_line_2' => $address->address_line_2,

                'delivery_city' => $address->city,

                'delivery_state_or_region' => $address->state_or_region,

                'delivery_postal_code' => $address->postal_code,

                'delivery_country_code' => $address->country_code,
            ]);

        $address->delete();

        $order->refresh();

        $this->assertNull(
            $order->delivery_address_id,
        );

        $this->assertSame(
            'Original Recipient',
            $order->delivery_recipient_name,
        );

        $this->assertSame(
            '123 Original Road',
            $order->delivery_address_line_1,
        );

        $this->assertSame(
            'Dhaka',
            $order->delivery_city,
        );
    }

    public function test_deleting_order_cascades_to_order_items(): void
    {
        $order = Order::factory()
            ->create();

        $item = OrderItem::factory()
            ->for($order)
            ->create();

        $order->delete();

        $this->assertDatabaseMissing(
            'order_items',
            [
                'id' => $item->id,
            ],
        );
    }

    public function test_order_money_is_stored_as_integer_minor_units(): void
    {
        $order = Order::factory()
            ->create([
                'subtotal' => 25000,
                'discount_total' => 1000,
                'delivery_total' => 150,
                'tax_total' => 0,
                'grand_total' => 24150,
            ]);

        $order->refresh();

        $this->assertSame(
            25000,
            $order->subtotal,
        );

        $this->assertSame(
            1000,
            $order->discount_total,
        );

        $this->assertSame(
            150,
            $order->delivery_total,
        );

        $this->assertSame(
            0,
            $order->tax_total,
        );

        $this->assertSame(
            24150,
            $order->grand_total,
        );
    }
}
