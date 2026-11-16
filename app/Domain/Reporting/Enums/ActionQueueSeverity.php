<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum ActionQueueSeverity: string
{
    case Normal =
        'normal';

    case Info =
        'info';

    case Warning =
        'warning';

    case High =
        'high';

    case Critical =
        'critical';

    public function rank(): int
    {
        return match ($this) {
            self::Normal => 0,

            self::Info => 10,

            self::Warning => 20,

            self::High => 30,

            self::Critical => 40,
        };
    }
}
