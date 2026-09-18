<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontFilterOptionsData
{
    /**
     * @param  list<StorefrontFilterOptionData>  $brands
     * @param  list<StorefrontFilterOptionData>  $categories
     * @param  list<StorefrontAttributeFilterData>  $attributes
     */
    public function __construct(
        public array $brands,
        public array $categories,
        public array $attributes,
        public ?int $minPrice,
        public ?int $maxPrice,
    ) {}

    /**
     * @return array{
     *     brands: list<array{
     *         id: int,
     *         name: string,
     *         slug: string
     *     }>,
     *     categories: list<array{
     *         id: int,
     *         name: string,
     *         slug: string
     *     }>,
     *     attributes: list<array{
     *         id: int,
     *         name: string,
     *         slug: string,
     *         values: list<array{
     *             id: int,
     *             name: string,
     *             slug: string
     *         }>
     *     }>,
     *     min_price: int|null,
     *     max_price: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'brands' => array_map(
                static fn (
                    StorefrontFilterOptionData $brand,
                ): array => $brand->toArray(),
                $this->brands,
            ),

            'categories' => array_map(
                static fn (
                    StorefrontFilterOptionData $category,
                ): array => $category->toArray(),
                $this->categories,
            ),

            'attributes' => array_map(
                static fn (
                    StorefrontAttributeFilterData $attribute,
                ): array => $attribute->toArray(),
                $this->attributes,
            ),

            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
        ];
    }
}
