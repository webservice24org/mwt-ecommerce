<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Workspace\Queries\GetAiSeoEntityWorkspaceQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;

final class AiSeoEntityWorkspaceController
{
    /**
     * @throws JsonException
     */
    public function __invoke(
        Request $request,
        string $entityType,
        string $entityId,
        GetAiSeoEntityWorkspaceQuery $workspace,
    ): Response {
        unset(
            $request,
        );

        $type =
            AiSeoEntityType::tryFrom(
                $entityType,
            );

        if (
            $type === null
            || ! ctype_digit(
                $entityId,
            )
            || (int) $entityId < 1
        ) {
            abort(
                404,
            );
        }

        return Inertia::render(
            'Admin/Seo/Entities/Show',
            [
                'workspace' => $workspace->handle(
                    $type,
                    (int) $entityId,
                ),
            ],
        );
    }
}
