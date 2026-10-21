<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_checkout_creates_order_and_item_snapshots(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'recipient_name' => 'Original Recipient',

                'phone' => '+8801700000000',

                'address_line_1' => '123 Checkout Road',

                'city' => 'Dhaka',

                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'name' => 'Checkout Product',

                'sku' => 'CHECKOUT-001',

                'price' => 25000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 2,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $idempotencyKey = app(
            CheckoutSessionService::class,
        )->orderSubmissionToken();

        $order = app(
            CreateOrderAction::class,
        )->execute(
            $user,
            $idempotencyKey,
        );

        $this->assertNull(
            session(
                'storefront.cart',
            ),
        );

        $this->assertSame(
            $user->id,
            $order->user_id,
        );

        $this->assertSame(
            $idempotencyKey,
            $order->idempotency_key,
        );

        $this->assertSame(
            $address->id,
            $order->delivery_address_id,
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );

        $this->assertSame(
            'BDT',
            $order->currency_code,
        );

        $this->assertSame(
            'Original Recipient',
            $order->delivery_recipient_name,
        );

        $this->assertSame(
            '123 Checkout Road',
            $order->delivery_address_line_1,
        );

        $this->assertCount(
            1,
            $order->items,
        );

        $item = $order
            ->items
            ->first();

        $this->assertNotNull(
            $item,
        );

        $this->assertSame(
            $product->id,
            $item->product_id,
        );

        $this->assertSame(
            'Checkout Product',
            $item->product_name,
        );

        $this->assertSame(
            'CHECKOUT-001',
            $item->sku,
        );

        $this->assertSame(
            2,
            $item->quantity,
        );
    }

    public function test_action_can_resolve_server_owned_idempotency_key_automatically(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'Automatic Token Product',

                'sku' => 'AUTO-TOKEN-001',

                'price' => 10000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $order = app(
            CreateOrderAction::class,
        )->execute(
            $user,
        );

        $this->assertNotNull(
            $order->idempotency_key,
        );

        $this->assertNotSame(
            '',
            $order->idempotency_key,
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,

                'idempotency_key' => $order->idempotency_key,
            ],
        );
    }

    public function test_order_preserves_address_snapshot_after_address_changes(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'recipient_name' => 'Original Recipient',

                'address_line_1' => 'Original Address',

                'city' => 'Dhaka',
            ]);

        $product = Product::factory()
            ->create([
                'price' => 10000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $idempotencyKey = app(
            CheckoutSessionService::class,
        )->orderSubmissionToken();

        $order = app(
            CreateOrderAction::class,
        )->execute(
            $user,
            $idempotencyKey,
        );

        $address->update([
            'recipient_name' => 'Changed Recipient',

            'address_line_1' => 'Changed Address',

            'city' => 'Chattogram',
        ]);

        $order->refresh();

        $this->assertSame(
            'Original Recipient',
            $order->delivery_recipient_name,
        );

        $this->assertSame(
            'Original Address',
            $order->delivery_address_line_1,
        );

        $this->assertSame(
            'Dhaka',
            $order->delivery_city,
        );
    }

    public function test_order_item_snapshot_survives_product_changes(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'Original Product',

                'sku' => 'ORIGINAL-SKU',

                'price' => 15000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $idempotencyKey = app(
            CheckoutSessionService::class,
        )->orderSubmissionToken();

        $order = app(
            CreateOrderAction::class,
        )->execute(
            $user,
            $idempotencyKey,
        );

        $product->update([
            'name' => 'Changed Product',

            'sku' => 'CHANGED-SKU',

            'price' => 99999,
        ]);

        $item = $order
            ->items()
            ->firstOrFail();

        $this->assertSame(
            'Original Product',
            $item->product_name,
        );

        $this->assertSame(
            'ORIGINAL-SKU',
            $item->sku,
        );

        $this->assertSame(
            15000,
            $item->unit_price,
        );
    }

    public function test_order_number_is_generated(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'price' => 10000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $idempotencyKey = app(
            CheckoutSessionService::class,
        )->orderSubmissionToken();

        $order = app(
            CreateOrderAction::class,
        )->execute(
            $user,
            $idempotencyKey,
        );

        $this->assertStringStartsWith(
            'ORD-',
            $order->order_number,
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,

                'order_number' => $order->order_number,

                'idempotency_key' => $idempotencyKey,
            ],
        );
    }

    public function test_same_idempotency_key_returns_existing_order(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'Idempotency Product',

                'sku' => 'IDEMPOTENCY-001',

                'price' => 20000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $idempotencyKey = app(
            CheckoutSessionService::class,
        )->orderSubmissionToken();

        $action = app(
            CreateOrderAction::class,
        );

        $firstOrder = $action
            ->execute(
                $user,
                $idempotencyKey,
            );

        /*
         * The first execution has already cleared
         * the cart.
         *
         * Repeating the same logical submission must
         * return the existing order before checkout
         * validation runs.
         */
        $secondOrder = $action
            ->execute(
                $user,
                $idempotencyKey,
            );

        $this->assertSame(
            $firstOrder->id,
            $secondOrder->id,
        );

        $this->assertSame(
            $idempotencyKey,
            $secondOrder->idempotency_key,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'order_items',
            1,
        );
    }

    public function test_successful_creator_rotates_submission_token(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'Rotation Product',

                'sku' => 'TOKEN-ROTATION-001',

                'price' => 12000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $checkoutSession = app(
            CheckoutSessionService::class,
        );

        $originalToken = $checkoutSession
            ->orderSubmissionToken();

        $order = app(
            CreateOrderAction::class,
        )->execute(
            $user,
            $originalToken,
        );

        $newToken = $checkoutSession
            ->orderSubmissionToken();

        $this->assertSame(
            $originalToken,
            $order->idempotency_key,
        );

        $this->assertNotSame(
            $originalToken,
            $newToken,
        );
    }

    public function test_different_users_can_use_same_idempotency_key(): void
    {
        $sharedKey =
            'shared-idempotency-key';

        $firstUser = User::factory()
            ->create();

        $firstAddress = Address::factory()
            ->for($firstUser)
            ->default()
            ->create();

        $firstProduct = Product::factory()
            ->create([
                'name' => 'First User Product',

                'sku' => 'IDEMPOTENCY-USER-1',

                'price' => 10000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $firstUser,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$firstProduct->id => [
                        'product_id' => $firstProduct->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $firstAddress->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $firstOrder = app(
            CreateOrderAction::class,
        )->execute(
            $firstUser,
            $sharedKey,
        );

        $secondUser = User::factory()
            ->create();

        $secondAddress = Address::factory()
            ->for($secondUser)
            ->default()
            ->create();

        $secondProduct = Product::factory()
            ->create([
                'name' => 'Second User Product',

                'sku' => 'IDEMPOTENCY-USER-2',

                'price' => 15000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $secondUser,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$secondProduct->id => [
                        'product_id' => $secondProduct->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $secondAddress->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $secondOrder = app(
            CreateOrderAction::class,
        )->execute(
            $secondUser,
            $sharedKey,
        );

        $this->assertNotSame(
            $firstOrder->id,
            $secondOrder->id,
        );

        $this->assertSame(
            $sharedKey,
            $firstOrder->idempotency_key,
        );

        $this->assertSame(
            $sharedKey,
            $secondOrder->idempotency_key,
        );

        $this->assertDatabaseCount(
            'orders',
            2,
        );
    }
}
