<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

final readonly class UpdateAttributeValueData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public int $position,
        public bool $isActive,
    ) {}
}
