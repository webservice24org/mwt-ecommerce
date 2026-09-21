<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Enums;

enum PageType: string
{
    case Standard = 'standard';
    case Home = 'home';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Home => 'Home',
        };
    }
}
