<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Enums;

enum SectionWidth: string
{
    case Container = 'container';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Container => 'Container',
            self::Full => 'Full Width',
        };
    }
}