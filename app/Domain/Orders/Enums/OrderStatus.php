<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
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
                self::Confirmed,
                self::Cancelled,
            ],

            self::Confirmed => [
                self::Processing,
                self::Cancelled,
            ],

            self::Processing => [
                self::Completed,
            ],

            self::Completed,
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
                self::Completed,
                self::Cancelled,
            ],
            true,
        );
    }
}
