<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Workspace\Queries\GetAiSeoSuggestionsInboxQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;

final class AiSeoSuggestionsInboxController
{
    /**
     * @throws JsonException
     */
    public function __invoke(
        Request $request,
        GetAiSeoSuggestionsInboxQuery $inbox,
    ): Response {
        return Inertia::render(
            'Admin/Seo/Suggestions/Index',
            [
                'inbox' => $inbox->handle(
                    search: $request
                        ->string(
                            'q',
                        )
                        ->toString(),

                    entityType: $request
                        ->string(
                            'entity_type',
                        )
                        ->toString(),

                    field: $request
                        ->string(
                            'field',
                        )
                        ->toString(),

                    provider: $request
                        ->string(
                            'provider',
                        )
                        ->toString(),

                    reviewStatus: $request
                        ->string(
                            'review_status',
                        )
                        ->toString(),

                    page: max(
                        1,
                        $request
                            ->integer(
                                'page',
                                1,
                            ),
                    ),
                ),
            ],
        );
    }
}
