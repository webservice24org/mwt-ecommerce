<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

final readonly class ReorderPageSectionsData
{
    /**
     * @param  list<int>  $sectionIds
     */
    public function __construct(
        public array $sectionIds,
    ) {}
}
