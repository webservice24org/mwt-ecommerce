<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Registry;

use App\Domain\PageBuilder\Data\CatalogSourceDefinitionData;
use App\Domain\PageBuilder\Enums\CatalogSourceType;

final class CatalogSourceRegistry
{
    /**
     * @return list<CatalogSourceDefinitionData>
     */
    public function all(): array
    {
        return [
            new CatalogSourceDefinitionData(
                type: CatalogSourceType::Featured,
                label: 'Featured Products',
                description: 'Automatically display products marked as featured in the catalog.',
            ),

            new CatalogSourceDefinitionData(
                type: CatalogSourceType::Manual,
                label: 'Manual Selection',
                description: 'Choose specific products and control exactly which products are eligible for this section.',
            ),

            new CatalogSourceDefinitionData(
                type: CatalogSourceType::Category,
                label: 'Category',
                description: 'Automatically display products from a selected category.',
            ),
        ];
    }

    public function has(string $type): bool
    {
        return CatalogSourceType::tryFrom($type) !== null;
    }

    public function get(string $type): ?CatalogSourceDefinitionData
    {
        $sourceType = CatalogSourceType::tryFrom($type);

        if ($sourceType === null) {
            return null;
        }

        foreach ($this->all() as $definition) {
            if ($definition->type === $sourceType) {
                return $definition;
            }
        }

        return null;
    }
}
