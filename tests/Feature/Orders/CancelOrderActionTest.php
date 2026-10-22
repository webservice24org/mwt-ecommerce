<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Orders\Actions\CancelOrderAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CancelOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_order_can_be_cancelled_and_simple_stock_is_restored(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->create([
                'type' => ProductType::Simple,

                'track_inventory' => true,

                'stock_quantity' => 7,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => $product->name,

                'sku' => $product->sku,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 3,

                'stock_was_decremented' => true,

                'line_total' => 30000,
            ]);

        $cancelled = app(
            CancelOrderAction::class,
        )->execute(
            $order,
            $user,
            'Customer changed their mind.',
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $cancelled->status,
        );

        $this->assertSame(
            10,
            $product
                ->fresh()
                ->stock_quantity,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $cancelled->payment_status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $cancelled->fulfilment_status,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => OrderStatus::Pending
                    ->value,

                'to_status' => OrderStatus::Cancelled
                    ->value,

                'actor_type' => $user->getMorphClass(),

                'actor_id' => $user->id,

                'note' => 'Customer changed their mind.',
            ],
        );
    }

    public function test_confirmed_order_can_be_cancelled(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Confirmed,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'Non inventory item',

                'sku' => null,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 1,

                'stock_was_decremented' => false,

                'line_total' => 10000,
            ]);

        $cancelled = app(
            CancelOrderAction::class,
        )->execute(
            $order,
            $user,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $cancelled->status,
        );
    }

    public function test_variable_product_stock_is_restored_to_original_variant(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->variable()
            ->create([
                'track_inventory' => true,
            ]);

        $variant = ProductVariant::factory()
            ->create([
                'product_id' => $product->id,

                'stock_quantity' => 4,

                'is_active' => false,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => $variant->id,

                'product_name' => $product->name,

                'sku' => $variant->sku,

                'variant_description' => $variant->name,

                'unit_price' => 12000,

                'quantity' => 2,

                'stock_was_decremented' => true,

                'line_total' => 24000,
            ]);

        app(
            CancelOrderAction::class,
        )->execute(
            $order,
            $user,
        );

        $this->assertSame(
            6,
            $variant
                ->fresh()
                ->stock_quantity,
        );
    }

    public function test_non_inventory_item_does_not_change_stock_when_cancelled(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->create([
                'type' => ProductType::Simple,

                'track_inventory' => false,

                'stock_quantity' => 25,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => $product->name,

                'sku' => $product->sku,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 4,

                'stock_was_decremented' => false,

                'line_total' => 40000,
            ]);

        app(
            CancelOrderAction::class,
        )->execute(
            $order,
            $user,
        );

        $this->assertSame(
            25,
            $product
                ->fresh()
                ->stock_quantity,
        );
    }

    public function test_second_cancellation_is_rejected_without_double_stock_restoration(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->create([
                'type' => ProductType::Simple,

                'track_inventory' => true,

                'stock_quantity' => 8,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => $product->name,

                'sku' => $product->sku,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 2,

                'stock_was_decremented' => true,

                'line_total' => 20000,
            ]);

        $action = app(
            CancelOrderAction::class,
        );

        $action->execute(
            $order,
            $user,
        );

        $this->assertSame(
            10,
            $product
                ->fresh()
                ->stock_quantity,
        );

        try {
            $action->execute(
                $order,
                $user,
            );

            $this->fail(
                'A cancelled order must not be cancelled again.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cancellation',
                $exception->errors(),
            );
        }

        $this->assertSame(
            10,
            $product
                ->fresh()
                ->stock_quantity,
        );

        $this->assertSame(
            1,
            $order
                ->statusHistories()
                ->where(
                    'to_status',
                    OrderStatus::Cancelled
                        ->value,
                )
                ->count(),
        );
    }

    public function test_processing_order_cannot_be_cancelled(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,
            ]);

        try {
            app(
                CancelOrderAction::class,
            )->execute(
                $order,
                $user,
            );

            $this->fail(
                'Processing order must not be directly cancelled.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cancellation',
                $exception->errors(),
            );
        }

        $this->assertSame(
            OrderStatus::Processing,
            $order
                ->fresh()
                ->status,
        );
    }

    public function test_unknown_historical_stock_provenance_blocks_automatic_cancellation(): void
    {
        $user = User::factory()
            ->create();

        $product = Product::factory()
            ->create([
                'stock_quantity' => 10,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => $product->name,

                'sku' => $product->sku,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 2,

                'stock_was_decremented' => null,

                'line_total' => 20000,
            ]);

        try {
            app(
                CancelOrderAction::class,
            )->execute(
                $order,
                $user,
            );

            $this->fail(
                'Unknown stock provenance must block automatic cancellation.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cancellation',
                $exception->errors(),
            );
        }

        $this->assertSame(
            OrderStatus::Pending,
            $order
                ->fresh()
                ->status,
        );

        $this->assertSame(
            10,
            $product
                ->fresh()
                ->stock_quantity,
        );
    }

    public function test_stock_restoration_rolls_back_if_later_item_cannot_be_restored(): void
    {
        $user = User::factory()
            ->create();

        $firstProduct = Product::factory()
            ->create([
                'type' => ProductType::Simple,

                'track_inventory' => true,

                'stock_quantity' => 5,
            ]);

        $missingProduct = Product::factory()
            ->create([
                'type' => ProductType::Simple,

                'track_inventory' => true,

                'stock_quantity' => 4,
            ]);

        $missingProductId =
            $missingProduct->id;

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $firstProduct->id,

                'variant_id' => null,

                'product_name' => $firstProduct->name,

                'sku' => $firstProduct->sku,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 2,

                'stock_was_decremented' => true,

                'line_total' => 20000,
            ]);

        $secondItem = $order
            ->items()
            ->create([
                'product_id' => $missingProductId,

                'variant_id' => null,

                'product_name' => $missingProduct->name,

                'sku' => $missingProduct->sku,

                'variant_description' => null,

                'unit_price' => 10000,

                'quantity' => 1,

                'stock_was_decremented' => true,

                'line_total' => 10000,
            ]);

        /*
         * Simulate a historical reference whose live product
         * target no longer exists.
         *
         * If your FK uses nullOnDelete(), this also demonstrates
         * the expected production state naturally.
         */
        $secondItem->update([
            'product_id' => null,
        ]);

        try {
            app(
                CancelOrderAction::class,
            )->execute(
                $order,
                $user,
            );

            $this->fail(
                'Cancellation should fail when one tracked inventory target cannot be restored.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cancellation',
                $exception->errors(),
            );
        }

        /*
         * The first item may have been incremented before the
         * second item failed, but the transaction must roll that
         * increment back.
         */
        $this->assertSame(
            5,
            $firstProduct
                ->fresh()
                ->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order
                ->fresh()
                ->status,
        );

        $this->assertDatabaseMissing(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'to_status' => OrderStatus::Cancelled
                    ->value,
            ],
        );
    }
}
