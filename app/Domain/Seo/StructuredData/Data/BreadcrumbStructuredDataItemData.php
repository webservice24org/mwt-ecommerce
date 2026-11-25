<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Data;

final readonly class BreadcrumbStructuredDataItemData
{
    public function __construct(
        public string $name,
        public ?string $path = null,
    ) {}
}
