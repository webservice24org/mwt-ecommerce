<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $order_id
 * @property string $invoice_number
 * @property Carbon $issued_at
 * @property string $customer_name
 * @property string $customer_email
 */
final class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'invoice_number',
        'customer_name',
        'customer_email',
        'issued_at',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
        );
    }

    /**
     * Invoice identity must never change after issuance.
     */
    protected static function booted(): void
    {
        self::updating(
            static function (Invoice $invoice): void {
                if (
                    $invoice->isDirty(
                        'invoice_number',
                    )
                ) {
                    throw new LogicException(
                        'An issued invoice number cannot be changed.',
                    );
                }

                if (
                    $invoice->isDirty(
                        'order_id',
                    )
                ) {
                    throw new LogicException(
                        'An invoice cannot be moved to another order.',
                    );
                }

                if (
                    $invoice->isDirty([
                        'customer_name',
                        'customer_email',
                    ])
                ) {
                    throw new LogicException(
                        'Issued invoice customer details cannot be changed.',
                    );
                }

                if (
                    $invoice->isDirty(
                        'issued_at',
                    )
                ) {
                    throw new LogicException(
                        'An invoice issue date cannot be changed.',
                    );
                }
            },
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }
}
