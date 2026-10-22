<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Actions\CompleteOrderAction;
use App\Domain\Orders\Actions\ConfirmOrderAction;
use App\Domain\Orders\Actions\DeliverOrderAction;
use App\Domain\Orders\Actions\ShipOrderAction;
use App\Domain\Orders\Actions\StartOrderProcessingAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Mail\Orders\OrderStatusChangedMail;
use App\Models\MailSetting;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class OrderTransitionActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_move_through_processing_lifecycle(): void
    {
        Mail::fake();

        $this->enableMail();

        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $order = app(
            ConfirmOrderAction::class,
        )->execute(
            $order,
            $user,
            'Confirmed for processing.',
        );

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->status,
        );

        /*
         * Invoice issuance begins only when the order
         * actually enters processing.
         */
        $this->assertDatabaseMissing(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );

        $order = app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
            $user,
            'Warehouse processing started.',
        );

        $order->loadMissing(
            'invoice',
        );

        $this->assertNotNull(
            $order->invoice,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($order): bool {
                return $mail->headline ===
                    'We are processing your order'
                    &&
                    $mail->order->invoiceUrl ===
                    route(
                        'customer.invoices.show',
                        $order->invoice,
                    );
            },
        );

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

        /*
         * Starting processing must issue exactly one
         * immutable invoice for this order.
         */
        $this->assertDatabaseHas(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );

        $invoice = $order
            ->invoice()
            ->firstOrFail();

        $this->assertStringStartsWith(
            'INV-',
            $invoice->invoice_number,
        );

        $this->assertNotNull(
            $invoice->issued_at,
        );

        $order = app(
            ShipOrderAction::class,
        )->execute(
            $order,
            $user,
            'Shipment dispatched.',
        );

        $this->assertSame(
            FulfilmentStatus::Shipped,
            $order->fulfilment_status,
        );

        $order = app(
            DeliverOrderAction::class,
        )->execute(
            $order,
            $user,
            'Delivery confirmed.',
        );

        $this->assertSame(
            FulfilmentStatus::Delivered,
            $order->fulfilment_status,
        );

        $order = app(
            CompleteOrderAction::class,
        )->execute(
            $order,
            $user,
            'Order completed.',
        );

        $this->assertSame(
            OrderStatus::Completed,
            $order->status,
        );

        $this->assertDatabaseCount(
            'order_status_histories',
            6,
        );
    }

    public function test_pending_order_cannot_start_processing_directly(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
            $user,
        );
    }

    public function test_order_cannot_be_completed_before_delivery(): void
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
                CompleteOrderAction::class,
            )->execute(
                $order,
                $user,
            );

            $this->fail(
                'Order should not complete before delivery.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'fulfilment_status',
                $exception->errors(),
            );
        }

        $order->refresh();

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );
    }

    public function test_repeating_confirmation_is_rejected_without_duplicate_history(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        $order = app(
            ConfirmOrderAction::class,
        )->execute(
            $order,
            $user,
        );

        try {
            app(
                ConfirmOrderAction::class,
            )->execute(
                $order,
                $user,
            );

            $this->fail(
                'Repeated confirmation should be rejected.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'status',
                $exception->errors(),
            );
        }

        $this->assertDatabaseCount(
            'order_status_histories',
            1,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => OrderStatusHistory::TYPE_ORDER,

                'from_status' => OrderStatus::Pending
                    ->value,

                'to_status' => OrderStatus::Confirmed
                    ->value,
            ],
        );
    }

    public function test_shipping_before_processing_is_rejected(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            ShipOrderAction::class,
        )->execute(
            $order,
            $user,
        );
    }

    private function enableMail(): void
    {
        MailSetting::current()
            ->update([
                'enabled' => true,
                'mailer' => 'smtp',
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'mailer-user',
                'password' => 'secret-password',
                'encryption' => 'tls',
                'from_address' => 'store@example.com',
                'from_name' => 'MWT Ecommerce',
                'admin_order_notification_email' => 'orders@example.com',
            ]);
    }
}
