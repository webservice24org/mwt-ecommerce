<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkReviewWorkspaceQuery;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;

final class AiSeoBulkReviewWorkspaceController
{
    /** @throws JsonException */
    public function __invoke(
        string $bulkJobId,
        GetAiSeoBulkReviewWorkspaceQuery $workspace,
    ): Response {
        if (
            ! ctype_digit($bulkJobId)
            || (int) $bulkJobId < 1
        ) {
            abort(404);
        }

        return Inertia::render(
            'Admin/Seo/Bulk/Review',
            [
                'workspace' => $workspace->handle((int) $bulkJobId),
            ],
        );
    }
}
