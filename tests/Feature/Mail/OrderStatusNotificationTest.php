<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Domain\Mail\Actions\SendOrderStatusNotificationAction;
use App\Domain\Mail\Data\OrderMailData;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Mail\Orders\OrderStatusChangedMail;
use App\Models\MailSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

final class OrderStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_order_queues_customer_status_email(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Confirmed,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->confirmed(
            $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($order): bool {
                return $mail->hasTo(
                    $order->user->email,
                )
                    && $mail->order->orderId ===
                    $order->id
                    && $mail->headline ===
                    'Your order has been confirmed'
                    && $mail->statusMessage ===
                    'We have confirmed your order and it will be prepared for processing shortly.';
            },
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            1,
        );
    }

    public function test_processing_order_queues_customer_status_email(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Processing,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->processing(
            $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($order): bool {
                return $mail->hasTo(
                    $order->user->email,
                )
                    && $mail->order->orderId ===
                    $order->id
                    && $mail->headline ===
                    'We are processing your order'
                    && $mail->statusMessage ===
                    'Your order is now being prepared for fulfilment.';
            },
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            1,
        );
    }

    public function test_shipped_order_queues_customer_status_email(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Shipped,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->shipped(
            $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($order): bool {
                return $mail->hasTo(
                    $order->user->email,
                )
                    && $mail->order->orderId ===
                    $order->id
                    && $mail->headline ===
                    'Your order has been shipped'
                    && $mail->statusMessage ===
                    'Your order has left our fulfilment process and is now on the way to you.';
            },
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            1,
        );
    }

    public function test_delivered_order_queues_customer_status_email(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Delivered,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->delivered(
            $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($order): bool {
                return $mail->hasTo(
                    $order->user->email,
                )
                    && $mail->order->orderId ===
                    $order->id
                    && $mail->headline ===
                    'Your order has been delivered'
                    && $mail->statusMessage ===
                    'Your order has been marked as delivered. Thank you for shopping with us.';
            },
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            1,
        );
    }

    public function test_cancelled_order_queues_customer_status_email_with_reason(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Cancelled,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->cancelled(
            order: $order,

            reason: 'Customer no longer needs the order.',
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($order): bool {
                return $mail->hasTo(
                    $order->user->email,
                )
                    && $mail->order->orderId ===
                    $order->id
                    && $mail->headline ===
                    'Your order has been cancelled'
                    && $mail->statusMessage ===
                    'Your order has been cancelled. Reason: Customer no longer needs the order.';
            },
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            1,
        );
    }

    public function test_cancelled_order_without_reason_queues_generic_customer_email(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Cancelled,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->cancelled(
            order: $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static fn (
                OrderStatusChangedMail $mail,
            ): bool => $mail->statusMessage ===
                'Your order has been cancelled.',
        );
    }

    public function test_status_notification_does_not_queue_when_mail_is_disabled(): void
    {
        Mail::fake();

        MailSetting::current()
            ->update([
                'enabled' => false,
            ]);

        $order =
            $this->createOrder(
                status: OrderStatus::Confirmed,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->confirmed(
            $order,
        );

        Mail::assertNothingQueued();
    }

    public function test_confirmed_notification_rejects_wrong_order_status(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Pending,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            SendOrderStatusNotificationAction::class,
        )->confirmed(
            $order,
        );
    }

    public function test_processing_notification_rejects_wrong_order_status(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Confirmed,

                fulfilmentStatus: FulfilmentStatus::Processing,
            );

        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            SendOrderStatusNotificationAction::class,
        )->processing(
            $order,
        );
    }

    public function test_processing_notification_rejects_wrong_fulfilment_status(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            SendOrderStatusNotificationAction::class,
        )->processing(
            $order,
        );
    }

    public function test_shipped_notification_rejects_wrong_fulfilment_status(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Processing,
            );

        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            SendOrderStatusNotificationAction::class,
        )->shipped(
            $order,
        );
    }

    public function test_delivered_notification_rejects_wrong_fulfilment_status(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Shipped,
            );

        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            SendOrderStatusNotificationAction::class,
        )->delivered(
            $order,
        );
    }

    public function test_cancelled_notification_rejects_non_cancelled_order(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Pending,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            SendOrderStatusNotificationAction::class,
        )->cancelled(
            order: $order,

            reason: 'Test reason',
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

    private function createOrder(
        OrderStatus $status,
        FulfilmentStatus $fulfilmentStatus,
    ): Order {
        $user =
            User::factory()
                ->create([
                    'name' => 'Test Customer',

                    'email' => 'customer@example.com',
                ]);

        $order =
            Order::factory()
                ->for(
                    $user,
                )
                ->create([
                    'status' => $status,

                    'fulfilment_status' => $fulfilmentStatus,
                ]);

        OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_name' => 'Test Product',

                'sku' => 'TEST-SKU',

                'variant_description' => 'Black / Large',

                'quantity' => 1,

                'unit_price' => 10000,

                'line_total' => 10000,
            ]);

        return $order->fresh([
            'user',
            'items',
            'invoice',
        ]);
    }

    public function test_customer_supplied_cancellation_reason_is_html_escaped(): void
    {
        $order =
            $this->createOrder(
                status: OrderStatus::Cancelled,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            );

        $mail =
            new OrderStatusChangedMail(
                order: OrderMailData::fromOrder(
                    $order,
                ),

                headline: 'Your order has been cancelled',

                statusMessage: 'Your order has been cancelled. Reason: <script>alert("xss")</script>',
            );

        $html =
            $mail->render();

        $this->assertStringNotContainsString(
            '<script>alert("xss")</script>',
            $html,
        );

        $this->assertStringContainsString(
            '&lt;script&gt;',
            $html,
        );
    }
}
