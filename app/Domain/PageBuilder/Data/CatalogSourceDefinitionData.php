<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\CatalogSourceType;

final readonly class CatalogSourceDefinitionData
{
    public function __construct(
        public CatalogSourceType $type,
        public string $label,
        public string $description,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     label: string,
     *     description: string
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'label' => $this->label,
            'description' => $this->description,
        ];
    }
}
