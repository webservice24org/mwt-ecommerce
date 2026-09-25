<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Registry\CatalogSourceRegistry;

final readonly class GetCatalogSourceDefinitionsQuery
{
    public function __construct(
        private CatalogSourceRegistry $registry,
    ) {}

    /**
     * @return list<array{
     *     type: string,
     *     label: string,
     *     description: string
     * }>
     */
    public function handle(): array
    {
        return array_map(
            static fn ($definition): array => $definition->toArray(),
            $this->registry->all(),
        );
    }
}
