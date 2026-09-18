<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum StorefrontProductSort: string
{
    case Newest = 'newest';
    case PriceAscending = 'price_asc';
    case PriceDescending = 'price_desc';
    case NameAscending = 'name_asc';
    case NameDescending = 'name_desc';
}
