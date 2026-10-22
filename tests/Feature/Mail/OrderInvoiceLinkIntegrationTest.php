<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Domain\Mail\Actions\SendOrderStatusNotificationAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Mail\Orders\OrderStatusChangedMail;
use App\Models\Invoice;
use App\Models\MailSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class OrderInvoiceLinkIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_email_has_no_invoice_link_when_invoice_does_not_exist(): void
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
            static fn (
                OrderStatusChangedMail $mail,
            ): bool => $mail->order->invoiceUrl ===
                null,
        );
    }

    public function test_processing_email_contains_customer_invoice_url_when_invoice_exists(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Processing,
            );

        $invoice =
            $this->createInvoice(
                $order,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->processing(
            $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($invoice): bool {
                return $mail
                    ->order
                    ->invoiceUrl ===
                    route(
                        'customer.invoices.show',
                        $invoice,
                    );
            },
        );
    }

    public function test_shipped_email_retains_customer_invoice_url(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Shipped,
            );

        $invoice =
            $this->createInvoice(
                $order,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->shipped(
            $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($invoice): bool {
                return $mail
                    ->order
                    ->invoiceUrl ===
                    route(
                        'customer.invoices.show',
                        $invoice,
                    );
            },
        );
    }

    public function test_delivered_email_retains_customer_invoice_url(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Delivered,
            );

        $invoice =
            $this->createInvoice(
                $order,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->delivered(
            $order,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($invoice): bool {
                return $mail
                    ->order
                    ->invoiceUrl ===
                    route(
                        'customer.invoices.show',
                        $invoice,
                    );
            },
        );
    }

    public function test_pre_processing_cancelled_email_has_no_invoice_link(): void
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

            reason: 'Customer changed their mind.',
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static fn (
                OrderStatusChangedMail $mail,
            ): bool => $mail->order->invoiceUrl ===
                null,
        );
    }

    public function test_existing_invoice_is_used_without_creating_another_invoice(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder(
                status: OrderStatus::Processing,

                fulfilmentStatus: FulfilmentStatus::Shipped,
            );

        $invoice =
            $this->createInvoice(
                $order,
            );

        app(
            SendOrderStatusNotificationAction::class,
        )->shipped(
            $order,
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => $invoice->id,

                'order_id' => $order->id,
            ],
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
                    'name' => 'Invoice Link Customer',

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
                'product_name' => 'Invoice Link Product',

                'sku' => 'INVOICE-LINK-001',

                'variant_description' => null,

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

    private function createInvoice(
        Order $order,
    ): Invoice {
        $invoice =
            Invoice::factory()
                ->create([
                    'order_id' => $order->id,
                ]);

        $order->unsetRelation(
            'invoice',
        );

        return $invoice;
    }
}
