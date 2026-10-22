<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Actions\IssueInvoiceAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class IssueInvoiceActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_order_can_be_invoiced(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,
            ]);

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        $this->assertInstanceOf(
            Invoice::class,
            $invoice,
        );

        $this->assertSame(
            $order->id,
            $invoice->order_id,
        );

        $this->assertStringStartsWith(
            'INV-',
            $invoice->invoice_number,
        );

        $this->assertNotNull(
            $invoice->issued_at,
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => $invoice->id,

                'order_id' => $order->id,

                'invoice_number' => $invoice->invoice_number,
            ],
        );
    }

    public function test_invoice_issuance_is_idempotent(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Processing,
            ]);

        $action = app(
            IssueInvoiceAction::class,
        );

        $firstInvoice = $action->execute(
            $order,
        );

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

        $this->assertDatabaseCount(
            'invoices',
            1,
        );
    }

    public function test_pending_order_cannot_be_invoiced(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Pending,
            ]);

        try {
            app(
                IssueInvoiceAction::class,
            )->execute(
                $order,
            );

            $this->fail(
                'Pending order must not be invoiced.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'invoice',
                $exception->errors(),
            );
        }

        $this->assertDatabaseCount(
            'invoices',
            0,
        );
    }

    public function test_confirmed_order_is_not_invoiced_before_processing(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        try {
            app(
                IssueInvoiceAction::class,
            )->execute(
                $order,
            );

            $this->fail(
                'Confirmed order must not be invoiced before processing starts.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertSame(
                'An invoice cannot be issued until order processing begins.',
                $exception->errors()['invoice'][0]
                ?? null,
            );
        }

        $this->assertDatabaseCount(
            'invoices',
            0,
        );
    }

    public function test_cancelled_order_cannot_be_invoiced(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Cancelled,
            ]);

        try {
            app(
                IssueInvoiceAction::class,
            )->execute(
                $order,
            );

            $this->fail(
                'Cancelled order must not be invoiced.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'invoice',
                $exception->errors(),
            );
        }

        $this->assertDatabaseCount(
            'invoices',
            0,
        );
    }

    public function test_completed_order_can_issue_missing_invoice_as_recovery(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Completed,

                'fulfilment_status' => FulfilmentStatus::Delivered,
            ]);

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        $this->assertSame(
            $order->id,
            $invoice->order_id,
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );
    }

    public function test_cod_order_can_be_invoiced_while_payment_is_still_unpaid(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Processing,
            ]);

        $invoice = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        $this->assertSame(
            $order->id,
            $invoice->order_id,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order
                ->fresh()
                ->payment_status,
        );
    }

    public function test_existing_invoice_is_returned_without_changing_identity(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Processing,
            ]);

        $existing = Invoice::factory()
            ->for(
                $order,
            )
            ->create([
                'invoice_number' => 'INV-20261021-KEEP1234',
            ]);

        $issued = app(
            IssueInvoiceAction::class,
        )->execute(
            $order,
        );

        $this->assertSame(
            $existing->id,
            $issued->id,
        );

        $this->assertSame(
            'INV-20261021-KEEP1234',
            $issued->invoice_number,
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );
    }
}
