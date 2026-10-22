<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderInitialStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_records_initial_status_history(): void
    {
        [
            'user' => $user,
            'address' => $address,
            'product' => $product,
        ] = $this->checkoutFixture();

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

        $this->assertDatabaseCount(
            'order_status_histories',
            3,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => null,

                'to_status' => OrderStatus::Pending
                    ->value,

                'actor_type' => $user->getMorphClass(),

                'actor_id' => $user->id,
            ],
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_PAYMENT,

                'from_status' => null,

                'to_status' => PaymentStatus::Unpaid
                    ->value,
            ],
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_FULFILMENT,

                'from_status' => null,

                'to_status' => FulfilmentStatus::Unfulfilled
                    ->value,
            ],
        );

        $this->assertCount(
            3,
            $order->statusHistories,
        );
    }

    public function test_idempotent_order_replay_does_not_duplicate_initial_history(): void
    {
        [
            'user' => $user,
            'address' => $address,
            'product' => $product,
        ] = $this->checkoutFixture();

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

        $idempotencyKey =
            $checkoutSession
                ->orderSubmissionToken();

        $action = app(
            CreateOrderAction::class,
        );

        $firstOrder = $action
            ->execute(
                $user,
                $idempotencyKey,
            );

        $secondOrder = $action
            ->execute(
                $user,
                $idempotencyKey,
            );

        $this->assertSame(
            $firstOrder->id,
            $secondOrder->id,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'order_status_histories',
            3,
        );
    }

    /**
     * @return array{
     *     user: User,
     *     address: Address,
     *     product: Product
     * }
     */
    private function checkoutFixture(): array
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->published()
            ->create([
                'name' => 'History Product',

                'sku' => 'HISTORY-001',

                'price' => 25000,

                'track_inventory' => false,
            ]);

        return [
            'user' => $user,

            'address' => $address,

            'product' => $product,
        ];
    }
}
