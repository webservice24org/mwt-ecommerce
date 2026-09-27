<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Data\ResolvedPageSectionData;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
use App\Models\Page;

final readonly class ResolvedPageSectionsQuery
{
    public function __construct(
        private PageSectionResolver $resolver,
    ) {}

    /**
     * @return list<ResolvedPageSectionData>
     */
    public function handle(
        Page $page,
    ): array {
        return $page
            ->enabledSections()
            ->get()
            ->map(
                fn ($section): ResolvedPageSectionData => $this
                    ->resolver
                    ->resolve($section),
            )
            ->values()
            ->all();
    }
}
