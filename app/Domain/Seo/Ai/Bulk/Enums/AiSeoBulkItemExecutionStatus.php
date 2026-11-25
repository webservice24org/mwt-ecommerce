<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Enums;

enum AiSeoBulkItemExecutionStatus: string
{
    case Planned = 'planned';
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Drifted = 'drifted';
    case Failed = 'failed';

    public function terminal(): bool
    {
        return match ($this) {
            self::Completed,
            self::Skipped,
            self::Drifted,
            self::Failed => true,

            self::Planned,
            self::Queued,
            self::Processing => false,
        };
    }
}
