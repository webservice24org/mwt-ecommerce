<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Services\InvoiceNumberGenerator;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class IssueInvoiceAction
{
    public function __construct(
        private InvoiceNumberGenerator $invoiceNumberGenerator,
    ) {}

    /**
     * Issue the single immutable invoice for an order.
     *
     * This operation is intentionally idempotent.
     *
     * @throws ValidationException
     */
    public function execute(
        Order $order,
    ): Invoice {
        return DB::transaction(
            function () use ($order): Invoice {
                /*
                 * Serialize invoice issuance for this order.
                 *
                 * Combined with invoices.order_id UNIQUE,
                 * this prevents duplicate invoices when two
                 * requests race.
                 */
                $lockedOrder = Order::query()
                    ->whereKey(
                        $order->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * IDEMPOTENCY FAST PATH
                 *
                 * Once an invoice exists, always return that
                 * same invoice instead of creating another.
                 */
                $existingInvoice = $lockedOrder
                    ->invoice()
                    ->first();

                if ($existingInvoice !== null) {
                    return $existingInvoice;
                }

                $this->assertInvoiceCanBeIssued(
                    $lockedOrder,
                );

                /*
                 * Snapshot customer identity at the moment
                 * the invoice is first issued.
                 *
                 * Invoice rendering must never depend on
                 * future changes to the customer's profile.
                 */
                $customer = $lockedOrder
                    ->user()
                    ->firstOrFail();

                return $lockedOrder
                    ->invoice()
                    ->create([
                        'invoice_number' => $this
                            ->invoiceNumberGenerator
                            ->generate(),

                        'customer_name' => $customer
                            ->name,

                        'customer_email' => $customer
                            ->email,

                        'issued_at' => now(),
                    ]);
            },
        );
    }

    /**
     * @throws ValidationException
     */
    private function assertInvoiceCanBeIssued(
        Order $order,
    ): void {
        if (
            in_array(
                $order->status,
                [
                    OrderStatus::Processing,
                    OrderStatus::Completed,
                ],
                true,
            )
        ) {
            return;
        }

        $message =
            match ($order->status) {
                OrderStatus::Pending => 'An invoice cannot be issued while the order is pending.',

                OrderStatus::Confirmed => 'An invoice cannot be issued until order processing begins.',

                default => 'A cancelled order cannot be invoiced.',
            };

        throw ValidationException::withMessages([
            'invoice' => $message,
        ]);
    }
}
