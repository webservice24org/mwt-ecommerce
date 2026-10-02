<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Enums;

enum PageLayout: string
{
    case FullWidth = 'full_width';
    case LeftSidebar = 'left_sidebar';
    case RightSidebar = 'right_sidebar';

    public function label(): string
    {
        return match ($this) {
            self::FullWidth => 'Full Width',
            self::LeftSidebar => 'Left Sidebar',
            self::RightSidebar => 'Right Sidebar',
        };
    }
}
