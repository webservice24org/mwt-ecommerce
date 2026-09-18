<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontVariantData
{
    /**
     * @param  list<StorefrontAttributeValueData>  $attributeValues
     */
    public function __construct(
        public int $id,
        public string $sku,
        public ?string $name,
        public bool $isDefault,
        public StorefrontPricingData $pricing,
        public array $attributeValues,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     sku: string,
     *     name: string|null,
     *     is_default: bool,
     *     pricing: array{
     *         price: int|null,
     *         compare_at_price: int|null,
     *         on_sale: bool
     *     },
     *     attribute_values: list<array{
     *         id: int,
     *         name: string,
     *         slug: string,
     *         attribute: array{
     *             id: int,
     *             name: string,
     *             slug: string
     *         }
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'is_default' => $this->isDefault,
            'pricing' => $this->pricing->toArray(),
            'attribute_values' => array_map(
                static fn (
                    StorefrontAttributeValueData $value,
                ): array => $value->toArray(),
                $this->attributeValues,
            ),
        ];
    }
}
