<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Merchant\Queries\GetMerchantFeedExportHistoryQuery;
use App\Domain\Seo\Merchant\Queries\GetMerchantReadinessAdminPageQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\MerchantReadinessWorkspaceRequest;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class MerchantReadinessController extends Controller
{
    public function __invoke(
        MerchantReadinessWorkspaceRequest $request,
        GetMerchantReadinessAdminPageQuery $query,
        GetMerchantFeedExportHistoryQuery $exports,
    ): Response {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        return Inertia::render(
            'Admin/Seo/Merchant/Readiness',
            [
                'workspace' => $query
                    ->get(
                        search: $request->search(),

                        status: $request->status(),

                        feed: $request->feed(),

                        availability: $request->availability(),

                        identifierState: $request->identifierState(),

                        severity: $request->severity(),

                        page: $request->page(),
                    )
                    ->toArray(),

                'exports' => $exports
                    ->get(
                        page: $request->exportPage(),
                    )
                    ->toArray(),
            ],
        );
    }
}
