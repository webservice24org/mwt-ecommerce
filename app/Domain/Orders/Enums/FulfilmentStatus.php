<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

enum FulfilmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Unfulfilled => 'Unfulfilled',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Returned => 'Returned',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Unfulfilled => [
                self::Processing,
            ],

            self::Processing => [
                self::Shipped,
            ],

            self::Shipped => [
                self::Delivered,
            ],

            self::Delivered => [
                self::Returned,
            ],

            self::Returned => [],
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
        return $this === self::Returned;
    }
}
