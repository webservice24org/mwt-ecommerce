<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Performance\Queries\GetUnifiedSeoPerformanceDashboardQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\UnifiedSeoPerformanceDashboardRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class UnifiedSeoPerformanceDashboardController extends Controller
{
    public function __invoke(
        UnifiedSeoPerformanceDashboardRequest $request,
        GetUnifiedSeoPerformanceDashboardQuery $query,
    ): Response {
        $this->authorize('viewAny', Product::class);

        return Inertia::render(
            'Admin/Seo/Performance/Overview',
            [
                'workspace' => $query->get(
                    searchRunPublicId: $request->searchRunPublicId(),
                    analyticsRunPublicId: $request->analyticsRunPublicId(),
                    dateFrom: $request->dateFrom(),
                    dateTo: $request->dateTo(),
                    pageSearch: $request->pageSearch(),
                    pagesPage: $request->pagesPage(),
                ),
            ],
        );
    }
}
