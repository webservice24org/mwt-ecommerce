<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkPlanQuery;
use Inertia\Inertia;
use Inertia\Response;

final class AiSeoBulkPlanShowController
{
    public function __invoke(
        string $bulkJobId,
        GetAiSeoBulkPlanQuery $plans,
    ): Response {
        if (
            ! ctype_digit(
                $bulkJobId,
            )
            || (int) $bulkJobId < 1
        ) {
            abort(
                404,
            );
        }

        return Inertia::render(
            'Admin/Seo/Bulk/Show',
            [
                'plan' => $plans->handle(
                    (int) $bulkJobId,
                ),
            ],
        );
    }
}
