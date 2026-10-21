<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CashOnDeliveryOrderContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_method_enum_contains_cash_on_delivery_contract(): void
    {
        $this->assertSame(
            'cash_on_delivery',
            PaymentMethod::CashOnDelivery->value,
        );
    }

    public function test_order_model_casts_payment_method_to_payment_method_enum(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'payment_method' => PaymentMethod::CashOnDelivery,
            ]);

        $order->refresh();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );

        $this->assertSame(
            'cash_on_delivery',
            $order
                ->payment_method
                ->value,
        );
    }

    public function test_checkout_defaults_to_cash_on_delivery(): void
    {
        $service = app(
            CheckoutSessionService::class,
        );

        $method = $service
            ->paymentMethod();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $method,
        );
    }

    public function test_new_cash_on_delivery_order_has_unpaid_payment_status(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,
            ]);

        $order->refresh();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status,
        );

        $this->assertSame(
            'unpaid',
            $order
                ->payment_status
                ->value,
        );
    }

    public function test_cash_on_delivery_order_starts_unpaid(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,
            ]);

        $order->refresh();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status,
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,

                'payment_method' => 'cash_on_delivery',

                'payment_status' => 'unpaid',
            ],
        );
    }

    public function test_successful_checkout_redirect_contains_order_confirmation_message(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'recipient_name' => 'Confirmation Recipient',

                'phone' => '+8801700000000',

                'address_line_1' => '123 Confirmation Road',

                'city' => 'Dhaka',

                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'name' => 'Confirmation Product',

                'sku' => 'CONFIRM-001',

                'price' => 25000,

                'status' => 'published',
            ]);

        $response = $this
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
            ])
            ->post(
                route(
                    'checkout.order.store',
                ),
            );

        $order = Order::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->latest('id')
            ->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'customer.orders.show',
                    $order->id,
                ),
            )
            ->assertSessionHas(
                'success',
                'Your order has been placed successfully.',
            );

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status,
        );
    }
}
