<?php

declare(strict_types=1);

namespace App\Domain\HeaderBuilder\Enums;

enum HeaderTemplate: string
{
    case MegaMenu = 'mega_menu';

    public function label(): string
    {
        return match ($this) {
            self::MegaMenu => 'Mega Menu',
        };
    }
}
