<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Audit\Queries\GetSeoAuditInternalLinkReviewWorkspaceQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\SeoAuditInternalLinkWorkspaceRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class SeoAuditInternalLinkWorkspaceController extends Controller
{
    public function __invoke(
        SeoAuditInternalLinkWorkspaceRequest $request,
        GetSeoAuditInternalLinkReviewWorkspaceQuery $query,
    ): Response {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        return Inertia::render(
            'Admin/Seo/InternalLinks/Index',
            [
                'workspace' => $query->get(
                    runPublicId: $request->runPublicId(),
                    state: $request->state(),
                    search: $request->search(),
                    page: $request->page(),
                ),
            ],
        );
    }
}
