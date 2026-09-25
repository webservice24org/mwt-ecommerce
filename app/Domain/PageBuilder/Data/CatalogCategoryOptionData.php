<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

final readonly class CatalogCategoryOptionData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
