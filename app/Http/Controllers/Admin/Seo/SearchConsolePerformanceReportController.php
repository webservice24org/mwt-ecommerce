<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Performance\Queries\GetSearchConsolePerformanceReportQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\SearchConsolePerformanceReportRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class SearchConsolePerformanceReportController extends Controller
{
    public function __invoke(
        SearchConsolePerformanceReportRequest $request,
        GetSearchConsolePerformanceReportQuery $query,
    ): Response {
        $this->authorize('viewAny', Product::class);

        return Inertia::render(
            'Admin/Seo/Performance/SearchConsole',
            [
                'workspace' => $query->get(
                    runPublicId: $request->runPublicId(),
                    dateFrom: $request->dateFrom(),
                    dateTo: $request->dateTo(),
                    querySearch: $request->querySearch(),
                    pageSearch: $request->pageSearch(),
                    runsPage: $request->runsPage(),
                    queriesPage: $request->queriesPage(),
                    pagesPage: $request->pagesPage(),
                ),
            ],
        );
    }
}
