<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PageBuilder\Data\PageDataFactory;
use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use App\Domain\PageBuilder\Queries\PageEditQuery;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

final class PageBuilderController extends Controller
{
    public function __invoke(
        Page $page,
        PageEditQuery $query,
        GetSectionDefinitionsQuery $sectionDefinitions,
        PageDataFactory $dataFactory,
    ): Response {
        $this->authorize(
            'update',
            $page,
        );

        $page = $query->findOrFail(
            $page->id,
        );

        return Inertia::render(
            'Admin/PageBuilder/Pages/Builder',
            [
                'page' => $dataFactory
                    ->fromModel($page)
                    ->toArray(),

                'sectionDefinitions' => array_map(
                    static fn (
                        SectionDefinitionData $definition,
                    ): array => $definition->toArray(),
                    $sectionDefinitions->handle(),
                ),
            ],
        );
    }
}
