<?php

namespace App\Domain\PageBuilder\Enums;

enum PageContentMode: string
{
    case Classic = 'classic';
    case Builder = 'builder';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Classic Editor',
            self::Builder => 'Page Builder',
        };
    }
}
