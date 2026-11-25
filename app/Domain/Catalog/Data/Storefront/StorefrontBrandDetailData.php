<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontBrandDetailData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $logoUrl,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     logo_url: string|null,
     *     meta_title: string|null,
     *     meta_description: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'description' => $this->description,

            'logo_url' => $this->logoUrl,

            'meta_title' => $this->metaTitle,

            'meta_description' => $this->metaDescription,
        ];
    }
}
