<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

use App\Models\Category;

final readonly class StorefrontCategoryDetailData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $imageUrl,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     image_url: string|null,
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
            'image_url' => $this->imageUrl,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
        ];
    }

    public static function fromModel(
        Category $category,
        ?string $imageUrl = null,
    ): self {
        return new self(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            description: $category->description,
            imageUrl: $imageUrl,
            metaTitle: $category->meta_title,
            metaDescription: $category->meta_description,
        );
    }
}
