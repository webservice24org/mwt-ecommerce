<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class InvoiceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_issued_invoice(): void
    {
        $admin =
            $this->createAdmin();

        $customer =
            $this->createCustomer(
                name: 'Invoice Customer',

                email: 'invoice-customer@example.com',
            );

        $invoice =
            $this->createIssuedInvoice(
                $customer,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.invoices.show',
                    $invoice,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Invoices/Show',
                    )
                    ->where(
                        'invoice.id',
                        $invoice->id,
                    )
                    ->where(
                        'invoice.invoice_number',
                        $invoice->invoice_number,
                    )
                    ->where(
                        'invoice.customer.name',
                        'Invoice Customer',
                    )
                    ->where(
                        'invoice.customer.email',
                        'invoice-customer@example.com',
                    )
                    ->where(
                        'invoice.order_number',
                        $invoice
                            ->order
                            ->order_number,
                    )
                    ->has(
                        'invoice.items',
                        1,
                    )
                    ->where(
                        'invoice.items.0.product_name',
                        'Historical Invoice Product',
                    )
                    ->where(
                        'invoice.items.0.unit_price',
                        25000,
                    )
                    ->where(
                        'invoice.items.0.quantity',
                        2,
                    )
                    ->where(
                        'invoice.items.0.line_total',
                        50000,
                    ),
            );
    }

    public function test_customer_can_view_own_issued_invoice(): void
    {
        $customer =
            $this->createCustomer(
                name: 'Invoice Owner',

                email: 'owner@example.com',
            );

        $invoice =
            $this->createIssuedInvoice(
                $customer,
            );

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Customer/Invoices/Show',
                    )
                    ->where(
                        'invoice.id',
                        $invoice->id,
                    )
                    ->where(
                        'invoice.invoice_number',
                        $invoice->invoice_number,
                    )
                    ->where(
                        'invoice.customer.name',
                        'Invoice Owner',
                    )
                    ->where(
                        'invoice.customer.email',
                        'owner@example.com',
                    ),
            );
    }

    public function test_customer_cannot_view_another_customers_invoice(): void
    {
        $owner =
            $this->createCustomer(
                name: 'Invoice Owner',

                email: 'owner@example.com',
            );

        $otherCustomer =
            $this->createCustomer(
                name: 'Other Customer',

                email: 'other@example.com',
            );

        $invoice =
            $this->createIssuedInvoice(
                $owner,
            );

        $this
            ->actingAs(
                $otherCustomer,
            )
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertNotFound();
    }

    public function test_guest_customer_cannot_view_invoice(): void
    {
        $customer =
            $this->createCustomer();

        $invoice =
            $this->createIssuedInvoice(
                $customer,
            );

        $this
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    public function test_guest_admin_cannot_view_invoice(): void
    {
        $customer =
            $this->createCustomer();

        $invoice =
            $this->createIssuedInvoice(
                $customer,
            );

        $this
            ->get(
                route(
                    'admin.invoices.show',
                    $invoice,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_invoice_views_receive_configured_branding(): void
    {
        $setting =
            InvoiceSetting::current();

        $setting->update([
            'company_name' => 'MicroWeb Commerce',

            'company_tagline' => 'Commerce Technology',

            'logo_path' => 'invoice-branding/company-logo.png',

            'header_start_color' => '#123456',

            'header_end_color' => '#345678',

            'footer_color' => '#111827',

            'support_email' => 'billing@example.com',

            'footer_credit' => 'Thank you for choosing MicroWeb Commerce.',
        ]);

        $admin =
            $this->createAdmin();

        $customer =
            $this->createCustomer();

        $invoice =
            $this->createIssuedInvoice(
                $customer,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.invoices.show',
                    $invoice,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'branding.company_name',
                        'MicroWeb Commerce',
                    )
                    ->where(
                        'branding.company_tagline',
                        'Commerce Technology',
                    )
                    ->where(
                        'branding.header_start_color',
                        '#123456',
                    )
                    ->where(
                        'branding.header_end_color',
                        '#345678',
                    )
                    ->where(
                        'branding.footer_color',
                        '#111827',
                    )
                    ->where(
                        'branding.support_email',
                        'billing@example.com',
                    )
                    ->where(
                        'branding.footer_credit',
                        'Thank you for choosing MicroWeb Commerce.',
                    ),
            );

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.invoices.show',
                    $invoice,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'branding.company_name',
                        'MicroWeb Commerce',
                    )
                    ->where(
                        'branding.footer_color',
                        '#111827',
                    )
                    ->where(
                        'branding.footer_credit',
                        'Thank you for choosing MicroWeb Commerce.',
                    ),
            );
    }

    public function test_invoice_view_uses_immutable_invoice_and_order_snapshots(): void
    {
        $admin =
            $this->createAdmin();

        $customer =
            $this->createCustomer(
                name: 'Original Customer',

                email: 'original@example.com',
            );

        $invoice =
            $this->createIssuedInvoice(
                $customer,
            );

        /*
         * Change live customer data after invoice
         * issuance.
         */
        $customer->update([
            'name' => 'Changed Customer',

            'email' => 'changed@example.com',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.invoices.show',
                    $invoice,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
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
                        'Historical Recipient',
                    )
                    ->where(
                        'invoice.delivery_address.address_line_1',
                        '42 Historical Road',
                    )
                    ->where(
                        'invoice.items.0.product_name',
                        'Historical Invoice Product',
                    )
                    ->where(
                        'invoice.items.0.sku',
                        'HIST-INVOICE-001',
                    )
                    ->where(
                        'invoice.subtotal',
                        50000,
                    )
                    ->where(
                        'invoice.discount_total',
                        5000,
                    )
                    ->where(
                        'invoice.delivery_total',
                        2000,
                    )
                    ->where(
                        'invoice.tax_total',
                        1000,
                    )
                    ->where(
                        'invoice.grand_total',
                        48000,
                    ),
            );
    }

    public function test_unknown_admin_invoice_returns_not_found(): void
    {
        $admin =
            $this->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.invoices.show',
                    999999999,
                ),
            )
            ->assertNotFound();
    }

    public function test_unknown_customer_invoice_returns_not_found(): void
    {
        $customer =
            $this->createCustomer();

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.invoices.show',
                    999999999,
                ),
            )
            ->assertNotFound();
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()
            ->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);
    }

    private function createCustomer(
        string $name = 'Customer',
        string $email = 'customer@example.com',
    ): User {
        return User::factory()
            ->create([
                'name' => $name,

                'email' => $email,

                'email_verified_at' => now(),
            ]);
    }

    private function createIssuedInvoice(
        User $customer,
    ): Invoice {
        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'order_number' => 'ORD-INVOICE-'
                        .str_pad(
                            (string) $customer->id,
                            6,
                            '0',
                            STR_PAD_LEFT,
                        ),

                    'status' => OrderStatus::Processing,

                    'payment_method' => PaymentMethod::CashOnDelivery,

                    'payment_status' => PaymentStatus::Unpaid,

                    'fulfilment_status' => FulfilmentStatus::Processing,

                    'currency_code' => 'BDT',

                    'subtotal' => 50000,

                    'discount_total' => 5000,

                    'delivery_total' => 2000,

                    'tax_total' => 1000,

                    'grand_total' => 48000,

                    'delivery_recipient_name' => 'Historical Recipient',

                    'delivery_phone' => '+8801700000000',

                    'delivery_address_line_1' => '42 Historical Road',

                    'delivery_address_line_2' => 'Floor 3',

                    'delivery_city' => 'Dhaka',

                    'delivery_state_or_region' => 'Dhaka Division',

                    'delivery_postal_code' => '1207',

                    'delivery_country_code' => 'BD',
                ]);

        $order
            ->items()
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'Historical Invoice Product',

                'sku' => 'HIST-INVOICE-001',

                'variant_description' => 'Black / Large',

                'unit_price' => 25000,

                'quantity' => 2,

                'stock_was_decremented' => false,

                'line_total' => 50000,
            ]);

        return Invoice::factory()
            ->for(
                $order,
            )
            ->create([
                'customer_name' => $customer->name,

                'customer_email' => $customer->email,
            ]);
    }
}
