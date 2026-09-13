<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

final readonly class UpdateCategoryData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?int $parentId,
        public ?string $description,
        public int $position,
        public bool $isActive,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}
}
