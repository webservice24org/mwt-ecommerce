<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Enums;

enum BrandSourceType: string
{
    case All = 'all';

    case Manual = 'manual';
}
