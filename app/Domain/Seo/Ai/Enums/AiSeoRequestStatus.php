<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Enums;

enum AiSeoRequestStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function terminal(): bool
    {
        return match ($this) {
            self::Completed,
            self::Failed,
            self::Cancelled => true,

            self::Pending,
            self::Processing => false,
        };
    }
}
