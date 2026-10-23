<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Actions\CancelOrderAction;
use App\Domain\Orders\Actions\ConfirmOrderAction;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class OrderCancellationStockSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_customer_cancellation_restores_exact_simple_product_stock(): void
    {
        $customer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(10)
            ->create([
                'price' => 10000,
            ]);

        $order = $this->createOrder(
            customer: $customer,
            address: $address,
            cart: [
                'product:'.$product->id => [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => 3,
                ],
            ],
        );

        /*
         * Checkout deducted exactly three.
         */
        $product->refresh();

        $this->assertSame(
            7,
            $product->stock_quantity,
        );

        $item = $order
            ->items()
            ->firstOrFail();

        $this->assertTrue(
            (bool) $item->getAttribute(
                'stock_was_decremented',
            ),
        );

        $paymentStatus =
            $order->payment_status;

        $fulfilmentStatus =
            $order->fulfilment_status;

        $cancelled = app(
            CancelOrderAction::class,
        )->executeForCustomer(
            order: $order,
            customer: $customer,
            reason: 'Customer changed their mind.',
        );

        $product->refresh();
        $cancelled->refresh();

        /*
         * Cancellation returns exactly what checkout
         * deducted: 7 + 3 = 10.
         */
        $this->assertSame(
            10,
            $product->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $cancelled->status,
        );

        /*
         * Cancellation changes the order state only.
         * Payment and fulfilment remain independent.
         */
        $this->assertSame(
            $paymentStatus,
            $cancelled->payment_status,
        );

        $this->assertSame(
            $fulfilmentStatus,
            $cancelled->fulfilment_status,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,
                'type' => OrderStatusHistory::TYPE_ORDER,
                'from_status' => OrderStatus::Pending->value,
                'to_status' => OrderStatus::Cancelled->value,
            ],
        );
    }

    public function test_variant_stock_is_restored_to_the_recorded_variant(): void
    {
        $customer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->variable()
            ->create([
                'track_inventory' => true,
                'stock_quantity' => 0,
            ]);

        $variant = ProductVariant::factory()
            ->for($product)
            ->withStock(8)
            ->create([
                'price' => 15000,
                'is_active' => true,
            ]);

        $order = $this->createOrder(
            customer: $customer,
            address: $address,
            cart: [
                'product:'
                .$product->id
                .':variant:'
                .$variant->id => [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 3,
                ],
            ],
        );

        $variant->refresh();
        $product->refresh();

        $this->assertSame(
            5,
            $variant->stock_quantity,
        );

        /*
         * Product-level stock is not the authority for
         * a variable-product line.
         */
        $this->assertSame(
            0,
            $product->stock_quantity,
        );

        /*
         * A historical order must restore against its
         * recorded variant even when that variant is no
         * longer purchasable.
         */
        $variant->update([
            'is_active' => false,
        ]);

        app(
            CancelOrderAction::class,
        )->executeForCustomer(
            order: $order,
            customer: $customer,
            reason: 'Cancel variant order.',
        );

        $variant->refresh();
        $product->refresh();

        $this->assertSame(
            8,
            $variant->stock_quantity,
        );

        $this->assertSame(
            0,
            $product->stock_quantity,
        );
    }

    public function test_non_inventory_item_is_not_artificially_added_to_stock(): void
    {
        $customer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->create([
                'price' => 10000,
                'track_inventory' => false,
                'stock_quantity' => 0,
            ]);

        $order = $this->createOrder(
            customer: $customer,
            address: $address,
            cart: [
                'product:'.$product->id => [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => 5,
                ],
            ],
        );

        $product->refresh();

        /*
         * Checkout did not decrement this line.
         */
        $this->assertSame(
            0,
            $product->stock_quantity,
        );

        $item = $order
            ->items()
            ->firstOrFail();

        $this->assertFalse(
            (bool) $item->getAttribute(
                'stock_was_decremented',
            ),
        );

        app(
            CancelOrderAction::class,
        )->executeForCustomer(
            order: $order,
            customer: $customer,
            reason: 'Cancel non-inventory item.',
        );

        $product->refresh();

        /*
         * Cancellation must not manufacture inventory
         * that was never deducted.
         */
        $this->assertSame(
            0,
            $product->stock_quantity,
        );
    }

    public function test_repeated_cancellation_cannot_restore_stock_twice(): void
    {
        $customer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(10)
            ->create([
                'price' => 10000,
            ]);

        $order = $this->createOrder(
            customer: $customer,
            address: $address,
            cart: [
                'product:'.$product->id => [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => 2,
                ],
            ],
        );

        $product->refresh();

        $this->assertSame(
            8,
            $product->stock_quantity,
        );

        /*
         * First request succeeds.
         */
        $this
            ->actingAs($customer)
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'First cancellation request.',
                ],
            )
            ->assertSessionHasNoErrors();

        $product->refresh();
        $order->refresh();

        $this->assertSame(
            10,
            $product->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $order->status,
        );

        $cancelHistoryCount =
            $order
                ->statusHistories()
                ->where(
                    'type',
                    OrderStatusHistory::TYPE_ORDER,
                )
                ->where(
                    'to_status',
                    OrderStatus::Cancelled->value,
                )
                ->count();

        $this->assertSame(
            1,
            $cancelHistoryCount,
        );

        /*
         * Replay / double-click / retried HTTP request.
         *
         * It must fail before inventory can be restored
         * a second time.
         */
        $this
            ->actingAs($customer)
            ->post(
                route(
                    'customer.orders.cancel',
                    $order,
                ),
                [
                    'reason' => 'Repeated cancellation request.',
                ],
            )
            ->assertSessionHasErrors([
                'cancellation',
            ]);

        $product->refresh();
        $order->refresh();

        /*
         * Crucial invariant:
         *
         * 10, not 12.
         */
        $this->assertSame(
            10,
            $product->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $order->status,
        );

        $this->assertSame(
            1,
            $order
                ->statusHistories()
                ->where(
                    'type',
                    OrderStatusHistory::TYPE_ORDER,
                )
                ->where(
                    'to_status',
                    OrderStatus::Cancelled->value,
                )
                ->count(),
        );
    }

    public function test_failed_multi_line_restoration_rolls_back_every_stock_change(): void
    {
        $customer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->default()
            ->create();

        $firstProduct = Product::factory()
            ->published()
            ->withStock(10)
            ->create([
                'price' => 10000,
            ]);

        $secondProduct = Product::factory()
            ->published()
            ->withStock(20)
            ->create([
                'price' => 20000,
            ]);

        $order = $this->createOrder(
            customer: $customer,
            address: $address,
            cart: [
                'product:'.$firstProduct->id => [
                    'product_id' => $firstProduct->id,
                    'variant_id' => null,
                    'quantity' => 2,
                ],

                'product:'.$secondProduct->id => [
                    'product_id' => $secondProduct->id,
                    'variant_id' => null,
                    'quantity' => 4,
                ],
            ],
        );

        $firstProduct->refresh();
        $secondProduct->refresh();

        $this->assertSame(
            8,
            $firstProduct->stock_quantity,
        );

        $this->assertSame(
            16,
            $secondProduct->stock_quantity,
        );

        $secondItem = $order
            ->items()
            ->where(
                'product_id',
                $secondProduct->id,
            )
            ->firstOrFail();

        /*
         * Simulate a legacy/corrupted order item whose
         * stock provenance is unknown.
         *
         * Automatic restoration must refuse to guess.
         */
        DB::table(
            'order_items',
        )
            ->where(
                'id',
                $secondItem->id,
            )
            ->update([
                'stock_was_decremented' => null,
            ]);

        try {
            app(
                CancelOrderAction::class,
            )->executeForCustomer(
                order: $order,
                customer: $customer,
                reason: 'Cancellation with ambiguous stock provenance.',
            );

            $this->fail(
                'Cancellation should fail when stock provenance is unknown.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertNotEmpty(
                $exception->errors(),
            );
        }

        $firstProduct->refresh();
        $secondProduct->refresh();
        $order->refresh();

        /*
         * The restoration and order state change are one
         * transaction.
         *
         * Even if one item had already been processed,
         * the database rollback must restore the original
         * post-checkout quantities.
         */
        $this->assertSame(
            8,
            $firstProduct->stock_quantity,
        );

        $this->assertSame(
            16,
            $secondProduct->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            0,
            $order
                ->statusHistories()
                ->where(
                    'type',
                    OrderStatusHistory::TYPE_ORDER,
                )
                ->where(
                    'to_status',
                    OrderStatus::Cancelled->value,
                )
                ->count(),
        );
    }

    public function test_confirmed_order_can_be_cancelled_internally_and_stock_is_restored_once(): void
    {
        $customer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(12)
            ->create([
                'price' => 10000,
            ]);

        $order = $this->createOrder(
            customer: $customer,
            address: $address,
            cart: [
                'product:'.$product->id => [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => 4,
                ],
            ],
        );

        $product->refresh();

        $this->assertSame(
            8,
            $product->stock_quantity,
        );

        $order = app(
            ConfirmOrderAction::class,
        )->execute(
            $order,
            $customer,
        );

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->status,
        );

        $order = app(
            CancelOrderAction::class,
        )->execute(
            order: $order,
            actor: $customer,
            reason: 'Internal cancellation after confirmation.',
        );

        $product->refresh();
        $order->refresh();

        $this->assertSame(
            12,
            $product->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $order->status,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,
                'type' => OrderStatusHistory::TYPE_ORDER,
                'from_status' => OrderStatus::Confirmed->value,
                'to_status' => OrderStatus::Cancelled->value,
            ],
        );
    }

    public function test_customer_cannot_cancel_confirmed_order_or_restore_its_stock(): void
    {
        $customer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->withStock(10)
            ->create([
                'price' => 10000,
            ]);

        $order = $this->createOrder(
            customer: $customer,
            address: $address,
            cart: [
                'product:'.$product->id => [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => 2,
                ],
            ],
        );

        $order = app(
            ConfirmOrderAction::class,
        )->execute(
            $order,
            $customer,
        );

        $product->refresh();

        $this->assertSame(
            8,
            $product->stock_quantity,
        );

        try {
            app(
                CancelOrderAction::class,
            )->executeForCustomer(
                order: $order,
                customer: $customer,
                reason: 'Customer tries after confirmation.',
            );

            $this->fail(
                'Confirmed order must not be directly customer-cancellable.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cancellation',
                $exception->errors(),
            );
        }

        $product->refresh();
        $order->refresh();

        /*
         * A rejected cancellation must have no stock
         * side effect.
         */
        $this->assertSame(
            8,
            $product->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->status,
        );
    }

    /**
     * @param array<
     *     string,
     *     array{
     *         product_id: int,
     *         variant_id: int|null,
     *         quantity: int
     *     }
     * > $cart
     */
    private function createOrder(
        User $customer,
        Address $address,
        array $cart,
    ): Order {
        $this
            ->actingAs($customer)
            ->withSession([
                'storefront.cart' => $cart,

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        return app(
            CreateOrderAction::class,
        )->execute(
            $customer,
        );
    }
}
