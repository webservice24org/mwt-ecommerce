<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Performance\Queries\GetGoogleAnalyticsPerformanceReportQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\GoogleAnalyticsPerformanceReportRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class GoogleAnalyticsPerformanceReportController extends Controller
{
    public function __invoke(
        GoogleAnalyticsPerformanceReportRequest $request,
        GetGoogleAnalyticsPerformanceReportQuery $query,
    ): Response {
        $this->authorize('viewAny', Product::class);

        return Inertia::render(
            'Admin/Seo/Performance/GoogleAnalytics',
            [
                'workspace' => $query->get(
                    runPublicId: $request->runPublicId(),
                    dateFrom: $request->dateFrom(),
                    dateTo: $request->dateTo(),
                    pageSearch: $request->pageSearch(),
                    runsPage: $request->runsPage(),
                    pagesPage: $request->pagesPage(),
                ),
            ],
        );
    }
}
