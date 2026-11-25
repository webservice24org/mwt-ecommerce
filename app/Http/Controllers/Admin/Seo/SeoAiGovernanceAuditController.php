<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\AiGovernance\Queries\GetSeoAiGovernanceAuditWorkspaceQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\SeoAiGovernanceAuditRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class SeoAiGovernanceAuditController extends Controller
{
    public function __invoke(
        SeoAiGovernanceAuditRequest $request,
        GetSeoAiGovernanceAuditWorkspaceQuery $query,
    ): Response {
        $this->authorize('viewAny', Product::class);

        return Inertia::render('Admin/Seo/AiGovernance/Index', [
            'workspace' => $query->get(
                runPublicId: $request->runPublicId(),
                status: $request->status(),
                approvalStatus: $request->approvalStatus(),
                purpose: $request->purpose(),
                provider: $request->provider(),
                search: $request->search(),
                dateFrom: $request->dateFrom(),
                dateTo: $request->dateTo(),
                page: $request->page(),
            ),
        ]);
    }
}
