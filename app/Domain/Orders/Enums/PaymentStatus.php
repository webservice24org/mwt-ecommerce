<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Unpaid => [
                self::Pending,
                self::Paid,
            ],

            self::Pending => [
                self::Paid,
                self::Failed,
            ],

            self::Failed => [
                self::Pending,
            ],

            self::Paid => [
                self::PartiallyRefunded,
                self::Refunded,
            ],

            self::PartiallyRefunded => [
                self::Refunded,
            ],

            self::Refunded => [],
        };
    }

    public function canTransitionTo(
        self $status,
    ): bool {
        return in_array(
            $status,
            $this->allowedTransitions(),
            true,
        );
    }

    public function isTerminal(): bool
    {
        return $this === self::Refunded;
    }
}
