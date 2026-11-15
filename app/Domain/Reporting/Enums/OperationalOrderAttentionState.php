<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum OperationalOrderAttentionState: string
{
    case None = 'none';

    case PendingTooLong =
        'pending_too_long';

    case ProcessingTooLong =
        'processing_too_long';

    case FulfilmentStale =
        'fulfilment_stale';

    case PaymentOverdue =
        'payment_overdue';

    case DeliveredPaymentDue =
        'delivered_payment_due';

    case ReturnedAttention =
        'returned_attention';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No attention required',

            self::PendingTooLong => 'Pending too long',

            self::ProcessingTooLong => 'Processing too long',

            self::FulfilmentStale => 'Fulfilment delayed',

            self::PaymentOverdue => 'Payment overdue',

            self::DeliveredPaymentDue => 'Delivered with payment due',

            self::ReturnedAttention => 'Returned order requires attention',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::None => 'normal',

            self::PendingTooLong,
            self::ProcessingTooLong,
            self::FulfilmentStale => 'warning',

            self::DeliveredPaymentDue,
            self::ReturnedAttention => 'high',

            self::PaymentOverdue => 'critical',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::None => 0,

            self::PendingTooLong => 10,

            self::ProcessingTooLong => 20,

            self::FulfilmentStale => 30,

            self::DeliveredPaymentDue => 40,

            self::ReturnedAttention => 50,

            self::PaymentOverdue => 60,
        };
    }
}
