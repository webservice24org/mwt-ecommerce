<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkWorkspaceQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AiSeoBulkWorkspaceController
{
    public function __invoke(
        Request $request,
        GetAiSeoBulkWorkspaceQuery $workspace,
    ): Response {
        $entityTypes = $request
            ->input(
                'entity_types',
                [],
            );

        if (! is_array($entityTypes)) {
            $entityTypes = [];
        }

        /** @var list<string> $entityTypes */
        $entityTypes = array_values(
            array_filter(
                $entityTypes,
                static fn (
                    mixed $value,
                ): bool => is_string($value),
            ),
        );

        return Inertia::render(
            'Admin/Seo/Bulk/Index',
            [
                'workspace' => $workspace->handle(
                    entityTypes: $entityTypes,
                    search: $request
                        ->string('q')
                        ->toString(),
                    state: $request
                        ->string('state')
                        ->toString(),
                ),
            ],
        );
    }
}
