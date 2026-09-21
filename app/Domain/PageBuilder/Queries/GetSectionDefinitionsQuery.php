<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;

final readonly class GetSectionDefinitionsQuery
{
    public function __construct(
        private SectionRegistry $registry,
    ) {}

    /**
     * @return list<SectionDefinitionData>
     */
    public function handle(): array
    {
        return array_map(
            static fn (
                SectionDefinition $definition,
            ): SectionDefinitionData => SectionDefinitionData::fromDefinition(
                $definition,
            ),
            $this->registry->all(),
        );
    }
}
