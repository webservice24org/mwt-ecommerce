<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Domain\Mail\Actions\SendOrderNotificationAction;
use App\Domain\Mail\Data\OrderMailData;
use App\Mail\Orders\NewOrderAdminMail;
use App\Mail\Orders\OrderPlacedCustomerMail;
use App\Mail\Orders\OrderStatusChangedMail;
use App\Models\Invoice;
use App\Models\MailSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class OrderMailArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_mailables_are_queue_ready(): void
    {
        $this->assertTrue(
            is_subclass_of(
                OrderPlacedCustomerMail::class,
                ShouldQueue::class,
            ),
        );

        $this->assertTrue(
            is_subclass_of(
                NewOrderAdminMail::class,
                ShouldQueue::class,
            ),
        );

        $this->assertTrue(
            is_subclass_of(
                OrderStatusChangedMail::class,
                ShouldQueue::class,
            ),
        );
    }

    public function test_customer_order_placed_mail_uses_expected_view(): void
    {
        $order =
            $this->createOrder();

        $mail =
            new OrderPlacedCustomerMail(
                OrderMailData::fromOrder(
                    $order,
                ),
            );

        $content =
            $mail->content();

        $this->assertSame(
            'mail.orders.customer-placed',
            $content->view,
        );
    }

    public function test_admin_new_order_mail_uses_expected_view(): void
    {
        $order =
            $this->createOrder();

        $mail =
            new NewOrderAdminMail(
                order: OrderMailData::fromOrder(
                    $order,
                ),

                adminOrderUrl: route(
                    'admin.orders.show',
                    $order,
                ),
            );

        $content =
            $mail->content();

        $this->assertSame(
            'mail.orders.admin-new-order',
            $content->view,
        );
    }

    public function test_status_changed_mail_uses_expected_view(): void
    {
        $order =
            $this->createOrder();

        $mail =
            new OrderStatusChangedMail(
                order: OrderMailData::fromOrder(
                    $order,
                ),

                headline: 'Your order is processing',

                statusMessage: 'We have started processing your order.',
            );

        $content =
            $mail->content();

        $this->assertSame(
            'mail.orders.status-changed',
            $content->view,
        );
    }

    public function test_customer_order_placed_notification_is_queued_to_customer_email(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendCustomerOrderPlaced(
            $order,
        );

        Mail::assertQueued(
            OrderPlacedCustomerMail::class,
            static fn (
                OrderPlacedCustomerMail $mail,
            ): bool => $mail->hasTo(
                $order
                    ->user
                    ->email,
            ),
        );
    }

    public function test_admin_new_order_notification_is_queued_to_configured_admin_email(): void
    {
        Mail::fake();

        $this->enableMail(
            adminNotificationEmail: 'orders-admin@example.com',
        );

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendAdminNewOrder(
            $order,
        );

        Mail::assertQueued(
            NewOrderAdminMail::class,
            static fn (
                NewOrderAdminMail $mail,
            ): bool => $mail->hasTo(
                'orders-admin@example.com',
            ),
        );
    }

    public function test_customer_status_changed_notification_is_queued_to_customer_email(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendCustomerStatusChanged(
            order: $order,

            headline: 'Your order is processing',

            statusMessage: 'We have started processing your order.',
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static fn (
                OrderStatusChangedMail $mail,
            ): bool => $mail->hasTo(
                $order
                    ->user
                    ->email,
            ),
        );
    }

    public function test_disabled_mail_settings_do_not_queue_customer_order_placed_email(): void
    {
        Mail::fake();

        MailSetting::current()
            ->update([
                'enabled' => false,
            ]);

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendCustomerOrderPlaced(
            $order,
        );

        Mail::assertNothingQueued();
    }

    public function test_disabled_mail_settings_do_not_queue_admin_new_order_email(): void
    {
        Mail::fake();

        MailSetting::current()
            ->update([
                'enabled' => false,
            ]);

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendAdminNewOrder(
            $order,
        );

        Mail::assertNothingQueued();
    }

    public function test_disabled_mail_settings_do_not_queue_customer_status_changed_email(): void
    {
        Mail::fake();

        MailSetting::current()
            ->update([
                'enabled' => false,
            ]);

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendCustomerStatusChanged(
            order: $order,

            headline: 'Order update',

            statusMessage: 'Your order status changed.',
        );

        Mail::assertNothingQueued();
    }

    public function test_missing_admin_notification_email_does_not_queue_admin_mail(): void
    {
        Mail::fake();

        $this->enableMail(
            adminNotificationEmail: null,
        );

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendAdminNewOrder(
            $order,
        );

        Mail::assertNothingQueued();
    }

    public function test_customer_order_mail_includes_invoice_url_when_invoice_exists(): void
    {
        Mail::fake();

        $this->enableMail();

        $order =
            $this->createOrder();

        $invoice =
            Invoice::factory()
                ->create([
                    'order_id' => $order->id,
                ]);

        $order->unsetRelation(
            'invoice',
        );

        app(
            SendOrderNotificationAction::class,
        )->sendCustomerOrderPlaced(
            $order,
        );

        Mail::assertQueued(
            OrderPlacedCustomerMail::class,
            static function (OrderPlacedCustomerMail $mail) use ($invoice): bool {
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

    public function test_admin_new_order_mail_includes_admin_invoice_url_when_invoice_exists(): void
    {
        Mail::fake();

        $this->enableMail(
            adminNotificationEmail: 'orders-admin@example.com',
        );

        $order =
            $this->createOrder();

        $invoice =
            Invoice::factory()
                ->create([
                    'order_id' => $order->id,
                ]);

        $order->unsetRelation(
            'invoice',
        );

        app(
            SendOrderNotificationAction::class,
        )->sendAdminNewOrder(
            $order,
        );

        Mail::assertQueued(
            NewOrderAdminMail::class,
            static function (NewOrderAdminMail $mail) use ($invoice): bool {
                return $mail
                    ->order
                    ->invoiceUrl ===
                    route(
                        'admin.invoices.show',
                        $invoice,
                    );
            },
        );
    }

    public function test_order_mail_data_uses_order_item_snapshots(): void
    {
        $order =
            $this->createOrder();

        $item =
            $order
                ->items()
                ->firstOrFail();

        $item->update([
            'product_name' => 'Historical Product Name',

            'sku' => 'HISTORICAL-SKU',

            'variant_description' => 'Historical variant',

            'unit_price' => 12500,

            'quantity' => 2,

            'line_total' => 25000,
        ]);

        $order->unsetRelation(
            'items',
        );

        $data =
            OrderMailData::fromOrder(
                $order,
            );

        $this->assertCount(
            1,
            $data->items,
        );

        $this->assertSame(
            'Historical Product Name',
            $data->items[0]['product_name'],
        );

        $this->assertSame(
            'HISTORICAL-SKU',
            $data->items[0]['sku'],
        );

        $this->assertSame(
            'Historical variant',
            $data->items[0]['variant_description'],
        );

        $this->assertSame(
            12500,
            $data->items[0]['unit_price'],
        );

        $this->assertSame(
            2,
            $data->items[0]['quantity'],
        );

        $this->assertSame(
            25000,
            $data->items[0]['line_total'],
        );
    }

    public function test_order_mail_data_does_not_require_live_product_relations(): void
    {
        $order =
            $this->createOrder();

        $item =
            $order
                ->items()
                ->firstOrFail();

        $item->update([
            'product_id' => null,

            'variant_id' => null,

            'product_name' => 'Snapshot-only product',

            'sku' => 'SNAPSHOT-001',
        ]);

        $order->unsetRelation(
            'items',
        );

        $data =
            OrderMailData::fromOrder(
                $order,
            );

        $this->assertSame(
            'Snapshot-only product',
            $data->items[0]['product_name'],
        );

        $this->assertSame(
            'SNAPSHOT-001',
            $data->items[0]['sku'],
        );
    }

    public function test_admin_mail_contains_admin_order_url(): void
    {
        Mail::fake();

        $this->enableMail(
            adminNotificationEmail: 'orders-admin@example.com',
        );

        $order =
            $this->createOrder();

        app(
            SendOrderNotificationAction::class,
        )->sendAdminNewOrder(
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

    private function enableMail(
        ?string $adminNotificationEmail =
        'admin@example.com',
    ): MailSetting {
        $setting =
            MailSetting::current();

        $setting->update([
            'enabled' => true,

            'mailer' => 'smtp',

            'host' => 'smtp.example.com',

            'port' => 587,

            'username' => 'mailer-user',

            'password' => 'secret-password',

            'encryption' => 'tls',

            'from_address' => 'orders@example.com',

            'from_name' => 'MWT Ecommerce',

            'admin_order_notification_email' => $adminNotificationEmail,
        ]);

        return $setting;
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

                'unit_price' => 10000,

                'quantity' => 1,

                'line_total' => 10000,
            ]);

        return $order
            ->fresh([
                'user',
                'items',
                'invoice',
            ]);
    }
}
