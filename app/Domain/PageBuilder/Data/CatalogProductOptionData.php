<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

final readonly class CatalogProductOptionData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $sku,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     sku: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
        ];
    }
}
