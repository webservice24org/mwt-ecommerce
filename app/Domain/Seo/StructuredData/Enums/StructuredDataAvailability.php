<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Enums;

enum StructuredDataAvailability: string
{
    case InStock =
        'https://schema.org/InStock';

    case OutOfStock =
        'https://schema.org/OutOfStock';
}
