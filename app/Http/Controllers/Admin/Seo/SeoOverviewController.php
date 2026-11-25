<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Workspace\Queries\GetSeoDashboardQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SeoOverviewController
{
    public function __invoke(
        Request $request,
        GetSeoDashboardQuery $dashboard,
    ): Response {
        return Inertia::render(
            'Admin/Seo/Overview',
            [
                'dashboard' => $dashboard->handle(
                    $request->string(
                        'entity_type',
                    )->toString(),
                ),
            ],
        );
    }
}
