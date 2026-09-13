<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

final readonly class CreateBrandData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $description,
        public int $position,
        public bool $isActive,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}
}
