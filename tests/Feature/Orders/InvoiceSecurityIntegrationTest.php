<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Actions\ConfirmOrderAction;
use App\Domain\Orders\Actions\IssueInvoiceAction;
use App\Domain\Orders\Actions\StartOrderProcessingAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Mail\Orders\OrderStatusChangedMail;
use App\Models\Invoice;
use App\Models\MailSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class InvoiceSecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_owned_invoice(): void
    {
        $customer = User::factory()->create([
            'name' => 'Invoice Owner',
            'email' => 'invoice-owner@example.com',
        ]);

        $order = $this->processingOrder(
            $customer,
        );

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Invoices/Show',
                    )
                    ->where(
                        'invoice.invoice_number',
                        $invoice->invoice_number,
                    )
                    ->where(
                        'invoice.order_number',
                        $order->order_number,
                    )
                    ->where(
                        'invoice.customer.name',
                        'Invoice Owner',
                    )
                    ->where(
                        'invoice.customer.email',
                        'invoice-owner@example.com',
                    ),
            );
    }

    public function test_customer_cannot_view_another_customers_invoice(): void
    {
        $owner = User::factory()->create();

        $attacker = User::factory()->create();

        $order = $this->processingOrder(
            $owner,
        );

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        /*
         * Return 404 rather than 403.
         *
         * A customer should not be told that another
         * customer's invoice identifier exists.
         */
        $this
            ->actingAs($attacker)
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertNotFound();
    }

    public function test_guest_cannot_view_customer_invoice(): void
    {
        $customer = User::factory()->create();

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $this->processingOrder(
                $customer,
            ),
        );

        $this
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertRedirect(
                route('login'),
            );
    }

    public function test_invoice_document_keeps_original_snapshots_after_mutable_records_change(): void
    {
        $customer = User::factory()->create([
            'name' => 'Original Customer',
            'email' => 'original@example.com',
        ]);

        $product = Product::factory()->create([
            'name' => 'Original Product Name',
            'sku' => 'ORIGINAL-SKU',
            'price' => 12500,
        ]);

        $order = Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,

                /*
                 * Immutable checkout delivery snapshot.
                 */
                'delivery_recipient_name' => 'Original Recipient',

                'delivery_phone' => '01700000000',

                'delivery_address_line_1' => '123 Original Road',

                'delivery_address_line_2' => 'Original Building',

                'delivery_city' => 'Dhaka',

                'delivery_state_or_region' => 'Dhaka',

                'delivery_postal_code' => '1200',

                'delivery_country_code' => 'BD',

                'currency_code' => 'BDT',

                'subtotal' => 25000,
                'discount_total' => 1000,
                'delivery_total' => 500,
                'tax_total' => 0,
                'grand_total' => 24500,
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                /*
                 * Immutable order-item snapshot.
                 */
                'product_name' => 'Original Product Name',

                'sku' => 'ORIGINAL-SKU',

                'variant_description' => null,

                'unit_price' => 12500,

                'quantity' => 2,

                'line_total' => 25000,
            ]);

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        /*
         * Change mutable source records AFTER invoice
         * issuance.
         */
        $customer->update([
            'name' => 'Changed Customer',

            'email' => 'changed@example.com',
        ]);

        $product->update([
            'name' => 'Changed Product Name',

            'sku' => 'CHANGED-SKU',

            'price' => 99999,
        ]);

        /*
         * Database-level invoice identity must remain
         * frozen.
         */
        $invoice->refresh();

        $this->assertSame(
            'Original Customer',
            $invoice->customer_name,
        );

        $this->assertSame(
            'original@example.com',
            $invoice->customer_email,
        );

        /*
         * More importantly, the rendered customer
         * document must continue using the immutable
         * invoice/order/item snapshots rather than
         * re-reading mutable profile/catalog data.
         */
        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'invoice.customer.name',
                        'Original Customer',
                    )
                    ->where(
                        'invoice.customer.email',
                        'original@example.com',
                    )
                    ->where(
                        'invoice.delivery_address.recipient_name',
                        'Original Recipient',
                    )
                    ->where(
                        'invoice.delivery_address.address_line_1',
                        '123 Original Road',
                    )
                    ->where(
                        'invoice.items.0.product_name',
                        'Original Product Name',
                    )
                    ->where(
                        'invoice.items.0.sku',
                        'ORIGINAL-SKU',
                    )
                    ->where(
                        'invoice.items.0.unit_price',
                        12500,
                    )
                    ->where(
                        'invoice.items.0.quantity',
                        2,
                    )
                    ->where(
                        'invoice.items.0.line_total',
                        25000,
                    )
                    ->where(
                        'invoice.subtotal',
                        25000,
                    )
                    ->where(
                        'invoice.discount_total',
                        1000,
                    )
                    ->where(
                        'invoice.delivery_total',
                        500,
                    )
                    ->where(
                        'invoice.grand_total',
                        24500,
                    ),
            );
    }

    public function test_repeated_invoice_issuance_returns_same_invoice_and_original_identity_snapshot(): void
    {
        $customer = User::factory()->create([
            'name' => 'First Identity',
            'email' => 'first@example.com',
        ]);

        $order = $this->processingOrder(
            $customer,
        );

        $action = app(
            IssueInvoiceAction::class,
        );

        $firstInvoice = $action->execute(
            $order,
        );

        /*
         * Identity changes after issuance.
         */
        $customer->update([
            'name' => 'Second Identity',
            'email' => 'second@example.com',
        ]);

        /*
         * Retry / repeated execution must return the
         * already-issued invoice.
         */
        $secondInvoice = $action->execute(
            $order,
        );

        $this->assertSame(
            $firstInvoice->id,
            $secondInvoice->id,
        );

        $this->assertSame(
            $firstInvoice->invoice_number,
            $secondInvoice->invoice_number,
        );

        $this->assertSame(
            'First Identity',
            $secondInvoice->customer_name,
        );

        $this->assertSame(
            'first@example.com',
            $secondInvoice->customer_email,
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );
    }

    public function test_processing_notification_contains_owned_customer_invoice_url(): void
    {
        Mail::fake();

        $this->enableMail();

        $customer = User::factory()->create([
            'email' => 'mail-customer@example.com',
        ]);

        $order = Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $order = app(
            ConfirmOrderAction::class,
        )->execute(
            $order,
            $customer,
            'Confirmed.',
        );

        $order = app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
            $customer,
            'Processing started.',
        );

        $invoice = $order
            ->invoice()
            ->firstOrFail();

        $expectedUrl = route(
            'customer.invoices.show',
            $invoice,
        );

        Mail::assertQueued(
            OrderStatusChangedMail::class,
            static function (OrderStatusChangedMail $mail) use ($expectedUrl): bool {
                return $mail->headline ===
                    'We are processing your order'
                    &&
                    $mail->order->invoiceUrl ===
                    $expectedUrl;
            },
        );

        /*
         * The recipient of that link can actually open it.
         */
        $this
            ->actingAs($customer)
            ->get(
                $expectedUrl,
            )
            ->assertOk();

        /*
         * But possession/guessing of the URL does not
         * bypass ownership.
         */
        $otherCustomer =
            User::factory()->create();

        $this
            ->actingAs($otherCustomer)
            ->get(
                $expectedUrl,
            )
            ->assertNotFound();
    }

    public function test_customer_order_page_exposes_only_the_owned_invoice_url(): void
    {
        $customer = User::factory()->create();

        $order = $this->processingOrder(
            $customer,
        );

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'order.invoice.number',
                        $invoice->invoice_number,
                    )
                    ->where(
                        'order.invoice.url',
                        route(
                            'customer.invoices.show',
                            $invoice,
                        ),
                    ),
            );
    }

    private function processingOrder(
        User $customer,
    ): Order {
        return Order::factory()
            ->for($customer)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,
            ]);
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
