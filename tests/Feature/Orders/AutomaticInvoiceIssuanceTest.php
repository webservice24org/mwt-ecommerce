<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Actions\StartOrderProcessingAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AutomaticInvoiceIssuanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_order_processing_automatically_issues_invoice(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $result = app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
        );

        $this->assertSame(
            OrderStatus::Processing,
            $result->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $result->fulfilment_status,
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'order_id' => $order->id,
            ],
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
    }

    public function test_confirmed_order_has_no_invoice_until_processing_starts(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $this->assertDatabaseMissing(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );

        app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );
    }

    public function test_cod_order_issues_invoice_without_marking_payment_paid(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Confirmed,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
        );

        $order->refresh();

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );

        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );
    }

    public function test_invalid_processing_transition_does_not_issue_invoice(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        try {
            app(
                StartOrderProcessingAction::class,
            )->execute(
                $order,
            );

            $this->fail(
                'Pending order must not start processing.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'status',
                $exception->errors(),
            );
        }

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Unfulfilled,
            $order->fulfilment_status,
        );

        $this->assertDatabaseMissing(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );
    }

    public function test_existing_invoice_is_reused_when_processing_starts(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        /*
         * Recovery/legacy scenario:
         * an invoice already exists before this transition.
         *
         * IssueInvoiceAction must return it rather than
         * creating a duplicate.
         */
        $existingInvoice = $order
            ->invoice()
            ->create([
                'invoice_number' => 'INV-20261021-EXISTING',

                'customer_name' => 'Existing Customer',

                'customer_email' => 'existing@example.com',

                'issued_at' => now(),
            ]);

        app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );

        $this->assertSame(
            $existingInvoice->id,
            $order
                ->invoice()
                ->firstOrFail()
                ->id,
        );
    }

    public function test_processing_transition_creates_both_histories_and_one_invoice(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        app(
            StartOrderProcessingAction::class,
        )->execute(
            $order,
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => 'order',

                'from_status' => OrderStatus::Confirmed->value,

                'to_status' => OrderStatus::Processing->value,
            ],
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,

                'type' => 'fulfilment',

                'from_status' => FulfilmentStatus::Unfulfilled
                    ->value,

                'to_status' => FulfilmentStatus::Processing
                    ->value,
            ],
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );
    }
}
