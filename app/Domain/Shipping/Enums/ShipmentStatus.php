<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Returned => 'Returned',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::Processing,
                self::Cancelled,
            ],

            self::Processing => [
                self::Shipped,
                self::Cancelled,
            ],

            self::Shipped => [
                self::Delivered,
                self::Returned,
            ],

            self::Delivered => [
                self::Returned,
            ],

            self::Returned,
            self::Cancelled => [],
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
        return in_array(
            $this,
            [
                self::Returned,
                self::Cancelled,
            ],
            true,
        );
    }
}
