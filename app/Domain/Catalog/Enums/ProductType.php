<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ProductType: string
{
    case Simple = 'simple';
    case Variable = 'variable';
}
