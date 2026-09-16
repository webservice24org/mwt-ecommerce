<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ProductVideoType: string
{
    case Upload = 'upload';
    case Youtube = 'youtube';
    case Vimeo = 'vimeo';

    public function label(): string
    {
        return match ($this) {
            self::Upload => 'Uploaded Video',
            self::Youtube => 'YouTube',
            self::Vimeo => 'Vimeo',
        };
    }
}
