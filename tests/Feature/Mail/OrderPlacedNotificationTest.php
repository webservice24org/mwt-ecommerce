<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Domain\Mail\Actions\SendOrderPlacedNotificationsAction;
use App\Mail\Orders\NewOrderAdminMail;
use App\Mail\Orders\OrderPlacedCustomerMail;
use App\Models\MailSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class OrderPlacedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_placed_notifications_queue_customer_and_admin_emails(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder();

        app(
            SendOrderPlacedNotificationsAction::class,
        )->execute(
            $order,
        );

        Mail::assertQueued(
            OrderPlacedCustomerMail::class,
            static fn (
                OrderPlacedCustomerMail $mail,
            ): bool => $mail->hasTo(
                'customer@example.com',
            ),
        );

        Mail::assertQueued(
            NewOrderAdminMail::class,
            static fn (
                NewOrderAdminMail $mail,
            ): bool => $mail->hasTo(
                'orders@example.com',
            ),
        );
    }

    public function test_customer_order_email_contains_order_snapshot(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder();

        app(
            SendOrderPlacedNotificationsAction::class,
        )->execute(
            $order,
        );

        Mail::assertQueued(
            OrderPlacedCustomerMail::class,
            static function (OrderPlacedCustomerMail $mail) use ($order): bool {
                return $mail
                    ->order
                    ->orderId ===
                    $order->id
                    &&
                    $mail
                        ->order
                        ->orderNumber ===
                    $order->order_number
                    &&
                    $mail
                        ->order
                        ->customerEmail ===
                    'customer@example.com';
            },
        );
    }

    public function test_admin_email_contains_order_management_url(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder();

        app(
            SendOrderPlacedNotificationsAction::class,
        )->execute(
            $order,
        );

        Mail::assertQueued(
            NewOrderAdminMail::class,
            static fn (
                NewOrderAdminMail $mail,
            ): bool => $mail->adminOrderUrl ===
                route(
                    'admin.orders.show',
                    $order,
                ),
        );
    }

    public function test_no_order_notifications_are_queued_when_mail_is_disabled(): void
    {
        Mail::fake();

        MailSetting::current()
            ->update([
                'enabled' => false,
            ]);

        $order =
            $this->createOrder();

        app(
            SendOrderPlacedNotificationsAction::class,
        )->execute(
            $order,
        );

        Mail::assertNothingQueued();
    }

    public function test_customer_email_is_still_queued_when_admin_recipient_is_not_configured(): void
    {
        Mail::fake();

        $this->enableMail(
            adminEmail: null,
        );

        $order =
            $this->createOrder();

        app(
            SendOrderPlacedNotificationsAction::class,
        )->execute(
            $order,
        );

        Mail::assertQueued(
            OrderPlacedCustomerMail::class,
            1,
        );

        Mail::assertNotQueued(
            NewOrderAdminMail::class,
        );
    }

    private function enableMail(
        ?string $adminEmail =
        'orders@example.com',
    ): void {
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

                'admin_order_notification_email' => $adminEmail,
            ]);
    }

    private function createOrder(): Order
    {
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
                ->create();

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
}
