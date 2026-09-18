<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

use App\Domain\Catalog\Enums\StorefrontProductSort;

final readonly class StorefrontProductFiltersData
{
    /**
     * @param  array<string, string>  $attributes
     */
    public function __construct(
        public StorefrontProductSort $sort,
        public ?string $brand,
        public ?string $category,
        public ?int $minPrice,
        public ?int $maxPrice,
        public array $attributes,
    ) {}

    /**
     * @param array{
     *     sort?: string|null,
     *     brand?: string|null,
     *     category?: string|null,
     *     min_price?: int|string|null,
     *     max_price?: int|string|null,
     *     attributes?: array<string, string>|null
     * } $data
     */
    public static function fromValidated(
        array $data,
    ): self {
        return new self(
            sort: StorefrontProductSort::tryFrom(
                (string) ($data['sort'] ?? ''),
            ) ?? StorefrontProductSort::Newest,

            brand: self::nullableString(
                $data['brand'] ?? null,
            ),

            category: self::nullableString(
                $data['category'] ?? null,
            ),

            minPrice: isset($data['min_price'])
                ? (int) $data['min_price']
                : null,

            maxPrice: isset($data['max_price'])
                ? (int) $data['max_price']
                : null,

            attributes: $data['attributes'] ?? [],
        );
    }

    private static function nullableString(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}
