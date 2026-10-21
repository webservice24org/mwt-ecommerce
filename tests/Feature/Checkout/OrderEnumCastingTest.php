<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderEnumCastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_state_fields_are_cast_to_domain_enums(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Pending,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $order->refresh();

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
    }

    public function test_initial_order_states_use_expected_values(): void
    {
        $order = Order::factory()
            ->create();

        $this->assertSame(
            'pending',
            $order->status->value,
        );

        $this->assertSame(
            'cash_on_delivery',
            $order
                ->payment_method
                ->value,
        );

        $this->assertSame(
            'unpaid',
            $order
                ->payment_status
                ->value,
        );

        $this->assertSame(
            'unfulfilled',
            $order
                ->fulfilment_status
                ->value,
        );
    }
}
