<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Enums;

enum SeoPerformanceConnectionStatus: string
{
    case Configured = 'configured';
    case Active = 'active';
    case Paused = 'paused';
    case Error = 'error';
}
