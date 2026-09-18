<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontAttributeFilterData
{
    /**
     * @param  list<StorefrontFilterOptionData>  $values
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public array $values,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     values: list<array{
     *         id: int,
     *         name: string,
     *         slug: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'values' => array_map(
                static fn (
                    StorefrontFilterOptionData $value,
                ): array => $value->toArray(),
                $this->values,
            ),
        ];
    }
}
