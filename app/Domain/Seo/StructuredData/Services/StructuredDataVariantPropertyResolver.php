<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Catalog\Data\Storefront\StorefrontAttributeValueData;

final class StructuredDataVariantPropertyResolver
{
    /**
     * @param  list<mixed>  $attributeValues
     * @return array<string, string>
     */
    public function properties(
        array $attributeValues,
    ): array {
        $properties = [];

        foreach ($attributeValues as $value) {
            if (
                ! $value instanceof StorefrontAttributeValueData
            ) {
                continue;
            }

            $property =
                $this->propertyName(
                    $value
                        ->attributeSlug,
                );

            if ($property === null) {
                continue;
            }

            $properties[$property] =
                $value->name;
        }

        return $properties;
    }

    /**
     * @param  list<StorefrontAttributeValueData>  $attributeValues
     * @return list<string>
     */
    public function variesBy(
        array $attributeValues,
    ): array {
        $values = [];

        foreach ($attributeValues as $value) {
            $property =
                $this->propertyName(
                    $value
                        ->attributeSlug,
                );

            if ($property === null) {
                continue;
            }

            $values[] =
                'https://schema.org/'
                .$property;
        }

        return array_values(
            array_unique(
                $values,
            ),
        );
    }

    private function propertyName(
        string $slug,
    ): ?string {
        return match (
            strtolower(
                trim(
                    $slug,
                ),
            )
        ) {
            'color',
            'colour' => 'color',

            'size' => 'size',

            'material' => 'material',

            'pattern' => 'pattern',

            default => null,
        };
    }
}
