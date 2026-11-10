<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Actions\CompleteOrderAction;
use App\Domain\Orders\Actions\MarkOrderPaidAction;
use App\Domain\Orders\Actions\MarkOrderPaymentDueAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CodPaymentCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_cod_order_can_be_marked_due(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->deliveredOrder(
                $user,
            );

        $dueAt =
            Carbon::parse(
                '2026-11-15',
            );

        $order = app(
            MarkOrderPaymentDueAction::class,
        )->execute(
            order: $order,

            paymentDueAt: $dueAt,

            actor: $user,

            note: 'Customer will pay later.',
        );

        $this->assertSame(
            PaymentStatus::Due,
            $order
                ->payment_status,
        );

        $this->assertNotNull(
            $order
                ->payment_due_at,
        );

        $this->assertNull(
            $order
                ->paid_at,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_PAYMENT,

                'from_status' => PaymentStatus::Unpaid
                    ->value,

                'to_status' => PaymentStatus::Due
                    ->value,
            ],
        );
    }

    public function test_due_cod_order_can_later_be_marked_paid(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->deliveredOrder(
                $user,
            );

        $order = app(
            MarkOrderPaymentDueAction::class,
        )->execute(
            order: $order,

            paymentDueAt: Carbon::parse(
                '2026-11-15',
            ),

            actor: $user,
        );

        $order = app(
            MarkOrderPaidAction::class,
        )->execute(
            order: $order,

            actor: $user,

            note: 'Payment collected.',
        );

        $this->assertSame(
            PaymentStatus::Paid,
            $order
                ->payment_status,
        );

        $this->assertNotNull(
            $order
                ->paid_at,
        );

        $this->assertNull(
            $order
                ->payment_due_at,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_PAYMENT,

                'from_status' => PaymentStatus::Due
                    ->value,

                'to_status' => PaymentStatus::Paid
                    ->value,
            ],
        );
    }

    public function test_delivered_unpaid_cod_order_can_be_marked_paid_directly(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->deliveredOrder(
                $user,
            );

        $order = app(
            MarkOrderPaidAction::class,
        )->execute(
            order: $order,

            actor: $user,
        );

        $this->assertSame(
            PaymentStatus::Paid,
            $order
                ->payment_status,
        );

        $this->assertNotNull(
            $order
                ->paid_at,
        );
    }

    public function test_unpaid_delivered_order_cannot_be_completed(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->deliveredOrder(
                $user,
            );

        try {
            app(
                CompleteOrderAction::class,
            )->execute(
                $order,
                $user,
            );

            $this->fail(
                'An unpaid delivered order should not complete.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'payment_status',
                $exception
                    ->errors(),
            );
        }

        $order->refresh();

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );
    }

    public function test_due_delivered_order_can_be_completed(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->deliveredOrder(
                $user,
            );

        $order = app(
            MarkOrderPaymentDueAction::class,
        )->execute(
            order: $order,

            paymentDueAt: Carbon::parse(
                '2026-11-15',
            ),

            actor: $user,
        );

        $order = app(
            CompleteOrderAction::class,
        )->execute(
            $order,
            $user,
        );

        $this->assertSame(
            OrderStatus::Completed,
            $order->status,
        );

        $this->assertSame(
            PaymentStatus::Due,
            $order
                ->payment_status,
        );
    }

    public function test_paid_delivered_order_can_be_completed(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->deliveredOrder(
                $user,
            );

        $order = app(
            MarkOrderPaidAction::class,
        )->execute(
            order: $order,

            actor: $user,
        );

        $order = app(
            CompleteOrderAction::class,
        )->execute(
            $order,
            $user,
        );

        $this->assertSame(
            OrderStatus::Completed,
            $order->status,
        );

        $this->assertSame(
            PaymentStatus::Paid,
            $order
                ->payment_status,
        );
    }

    private function deliveredOrder(
        User $user,
    ): Order {
        return Order::factory()
            ->for(
                $user,
            )
            ->create([
                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Delivered,

                'grand_total' => 77000,

                'payment_due_at' => null,

                'paid_at' => null,
            ]);
    }
}
