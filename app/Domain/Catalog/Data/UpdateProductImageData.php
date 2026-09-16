<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

final readonly class UpdateProductImageData
{
    public function __construct(
        public ?string $altText,
    ) {}
}
