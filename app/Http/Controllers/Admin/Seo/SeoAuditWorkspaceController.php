<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Audit\Queries\GetSeoAuditAdminWorkspaceQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\SeoAuditWorkspaceRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class SeoAuditWorkspaceController extends Controller
{
    public function __invoke(
        SeoAuditWorkspaceRequest $request,
        GetSeoAuditAdminWorkspaceQuery $query,
    ): Response {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        return Inertia::render(
            'Admin/Seo/Audit/Index',
            [
                'workspace' => $query->get(
                    runPublicId: $request->runPublicId(),
                    severity: $request->severity(),
                    rule: $request->rule(),
                    search: $request->search(),
                    runsPage: $request->runsPage(),
                    findingsPage: $request->findingsPage(),
                ),
            ],
        );
    }
}
