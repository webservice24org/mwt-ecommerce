<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontAttributeValueData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $attributeId,
        public string $attributeName,
        public string $attributeSlug,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     attribute: array{
     *         id: int,
     *         name: string,
     *         slug: string
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'attribute' => [
                'id' => $this->attributeId,
                'name' => $this->attributeName,
                'slug' => $this->attributeSlug,
            ],
        ];
    }
}
