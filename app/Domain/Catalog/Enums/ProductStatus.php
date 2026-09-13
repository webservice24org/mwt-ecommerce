<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
