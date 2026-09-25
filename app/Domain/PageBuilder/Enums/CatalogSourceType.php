<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Enums;

enum CatalogSourceType: string
{
    case Featured = 'featured';
    case Manual = 'manual';
    case Category = 'category';

    public function label(): string
    {
        return match ($this) {
            self::Featured => 'Featured Products',
            self::Manual => 'Manual Selection',
            self::Category => 'Category',
        };
    }
}
