<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Domain\Orders\Services\InvoiceNumberGenerator;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class InvoiceDomainFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_belongs_to_order(): void
    {
        $order = Order::factory()
            ->create();

        $invoice = Invoice::factory()
            ->for(
                $order,
            )
            ->create();

        $this->assertTrue(
            $invoice
                ->order
                ->is(
                    $order,
                ),
        );

        $this->assertTrue(
            $order
                ->invoice
                ?->is(
                    $invoice,
                )
            ?? false,
        );
    }

    public function test_only_one_invoice_can_exist_for_an_order(): void
    {
        $order = Order::factory()
            ->create();

        Invoice::factory()
            ->for(
                $order,
            )
            ->create();

        $this->expectException(
            QueryException::class,
        );

        Invoice::factory()
            ->for(
                $order,
            )
            ->create();
    }

    public function test_invoice_number_is_globally_unique(): void
    {
        $invoiceNumber =
            'INV-20261021-ABC12345';

        Invoice::factory()
            ->create([
                'invoice_number' => $invoiceNumber,
            ]);

        $this->expectException(
            QueryException::class,
        );

        Invoice::factory()
            ->create([
                'invoice_number' => $invoiceNumber,
            ]);
    }

    public function test_invoice_number_cannot_be_changed_after_creation(): void
    {
        $invoice = Invoice::factory()
            ->create();

        $originalNumber =
            $invoice->invoice_number;

        try {
            $invoice->update([
                'invoice_number' => 'INV-CHANGED',
            ]);

            $this->fail(
                'An issued invoice number must be immutable.',
            );
        } catch (
            LogicException $exception
        ) {
            $this->assertSame(
                'An issued invoice number cannot be changed.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(
            $originalNumber,
            $invoice
                ->fresh()
                ->invoice_number,
        );
    }

    public function test_invoice_cannot_be_moved_to_another_order(): void
    {
        $firstOrder = Order::factory()
            ->create();

        $secondOrder = Order::factory()
            ->create();

        $invoice = Invoice::factory()
            ->for(
                $firstOrder,
            )
            ->create();

        try {
            $invoice->update([
                'order_id' => $secondOrder->id,
            ]);

            $this->fail(
                'An invoice must not be movable to another order.',
            );
        } catch (
            LogicException $exception
        ) {
            $this->assertSame(
                'An invoice cannot be moved to another order.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(
            $firstOrder->id,
            $invoice
                ->fresh()
                ->order_id,
        );
    }

    public function test_invoice_number_generator_uses_invoice_contract(): void
    {
        $number = app(
            InvoiceNumberGenerator::class,
        )->generate();

        $this->assertMatchesRegularExpression(
            '/^INV-\d{8}-[A-Z0-9]{8}$/',
            $number,
        );
    }

    public function test_invoice_number_generator_avoids_existing_number(): void
    {
        $generator = app(
            InvoiceNumberGenerator::class,
        );

        $firstNumber =
            $generator->generate();

        Invoice::factory()
            ->create([
                'invoice_number' => $firstNumber,
            ]);

        $secondNumber =
            $generator->generate();

        $this->assertNotSame(
            $firstNumber,
            $secondNumber,
        );
    }
}
