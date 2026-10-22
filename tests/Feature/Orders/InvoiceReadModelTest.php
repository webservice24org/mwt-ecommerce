<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Actions\IssueInvoiceAction;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Queries\GetInvoiceDataQuery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoiceReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_read_model_uses_historical_order_snapshots(): void
    {
        $customer = User::factory()
            ->create([
                'name' => 'Original Customer',

                'email' => 'original@example.com',
            ]);

        $product = Product::factory()
            ->create([
                'name' => 'Current Product',

                'sku' => 'CURRENT-SKU',

                'price' => 99999,
            ]);

        $order = Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => OrderStatus::Processing,

                'currency_code' => 'BDT',

                'subtotal' => 25000,

                'discount_total' => 2000,

                'delivery_total' => 1000,

                'tax_total' => 500,

                'grand_total' => 24500,

                'delivery_recipient_name' => 'Historical Recipient',

                'delivery_phone' => '+8801700000000',

                'delivery_address_line_1' => '123 Historical Road',

                'delivery_address_line_2' => 'Floor 5',

                'delivery_city' => 'Dhaka',

                'delivery_state_or_region' => 'Dhaka Division',

                'delivery_postal_code' => '1207',

                'delivery_country_code' => 'BD',
            ]);

        $order
            ->items()
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => 'Historical Product',

                'sku' => 'HISTORICAL-SKU',

                'variant_description' => 'Historical Variant',

                'unit_price' => 12500,

                'quantity' => 2,

                'stock_was_decremented' => false,

                'line_total' => 25000,
            ]);

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        /*
         * Change live customer/catalog data after the
         * invoice has been issued.
         */
        $customer->update([
            'name' => 'Changed Customer',

            'email' => 'changed@example.com',
        ]);

        $product->update([
            'name' => 'Changed Product',

            'sku' => 'CHANGED-SKU',

            'price' => 77777,
        ]);

        $data = app(
            GetInvoiceDataQuery::class,
        )
            ->execute(
                $invoice,
            )
            ->toArray();

        $this->assertSame(
            'Original Customer',
            $data['customer']['name'],
        );

        $this->assertSame(
            'original@example.com',
            $data['customer']['email'],
        );

        $this->assertSame(
            'Historical Product',
            $data['items'][0]['product_name'],
        );

        $this->assertSame(
            'HISTORICAL-SKU',
            $data['items'][0]['sku'],
        );

        $this->assertSame(
            12500,
            $data['items'][0]['unit_price'],
        );

        $this->assertSame(
            25000,
            $data['items'][0]['line_total'],
        );

        $this->assertSame(
            'Historical Recipient',
            $data['delivery_address']['recipient_name'],
        );

        $this->assertSame(
            '123 Historical Road',
            $data['delivery_address']['address_line_1'],
        );

        $this->assertSame(
            25000,
            $data['subtotal'],
        );

        $this->assertSame(
            2000,
            $data['discount_total'],
        );

        $this->assertSame(
            1000,
            $data['delivery_total'],
        );

        $this->assertSame(
            500,
            $data['tax_total'],
        );

        $this->assertSame(
            24500,
            $data['grand_total'],
        );
    }

    public function test_reissuing_invoice_does_not_refresh_customer_snapshot(): void
    {
        $customer = User::factory()
            ->create([
                'name' => 'Original Name',

                'email' => 'original@example.com',
            ]);

        $order = Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => OrderStatus::Processing,
            ]);

        $action = app(
            IssueInvoiceAction::class,
        );

        $first = $action->execute(
            $order,
        );

        $customer->update([
            'name' => 'Changed Name',

            'email' => 'changed@example.com',
        ]);

        $second = $action->execute(
            $order,
        );

        $this->assertSame(
            $first->id,
            $second->id,
        );

        $this->assertSame(
            'Original Name',
            $second->customer_name,
        );

        $this->assertSame(
            'original@example.com',
            $second->customer_email,
        );
    }

    public function test_issued_at_cannot_be_changed(): void
    {
        $invoice = Invoice::factory()
            ->create();

        $this->expectException(
            \LogicException::class,
        );

        $invoice->update([
            'issued_at' => now()->addDay(),
        ]);
    }

    public function test_invoice_customer_snapshot_cannot_be_changed(): void
    {
        $invoice = Invoice::factory()
            ->create();

        $this->expectException(
            \LogicException::class,
        );

        $invoice->update([
            'customer_name' => 'Changed Customer',
        ]);
    }

    public function test_invoice_read_model_handles_optional_snapshot_values(): void
    {
        $invoice =
            Invoice::factory()
                ->create();

        $order =
            $invoice->order;

        $order->update([
            'delivery_address_line_2' => null,

            'delivery_state_or_region' => null,

            'delivery_postal_code' => null,
        ]);

        $data = app(
            GetInvoiceDataQuery::class,
        )
            ->execute(
                $invoice->fresh(),
            )
            ->toArray();

        $this->assertNull(
            $data['delivery_address']['address_line_2'],
        );

        $this->assertNull(
            $data['delivery_address']['state_or_region'],
        );

        $this->assertNull(
            $data['delivery_address']['postal_code'],
        );
    }
}
