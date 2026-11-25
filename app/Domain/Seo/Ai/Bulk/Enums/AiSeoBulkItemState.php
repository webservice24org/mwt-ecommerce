<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Enums;

enum AiSeoBulkItemState: string
{
    case Ready = 'ready';
    case Stale = 'stale';
    case Fresh = 'fresh';

    public function isEligibleForGeneration(): bool
    {
        return match ($this) {
            self::Ready,
            self::Stale => true,
            self::Fresh => false,
        };
    }
}
