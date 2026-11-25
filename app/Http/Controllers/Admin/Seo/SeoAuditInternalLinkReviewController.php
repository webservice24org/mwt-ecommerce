<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Audit\Services\SeoAuditInternalLinkReviewService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\ReviewSeoAuditInternalLinkOpportunityRequest;
use App\Models\Admin;
use App\Models\Product;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class SeoAuditInternalLinkReviewController extends Controller
{
    public function update(
        ReviewSeoAuditInternalLinkOpportunityRequest $request,
        SeoAuditRun $run,
        string $fingerprint,
        SeoAuditInternalLinkReviewService $service,
    ): RedirectResponse {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        $admin = $request->user('admin');

        if (! $admin instanceof Admin) {
            abort(403);
        }

        try {
            $opportunity = $service->review(
                run: $run,
                fingerprint: $fingerprint,
                state: $request->reviewState(),
                admin: $admin,
                note: $request->note(),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'review' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            sprintf(
                'Internal link opportunity %s. This records review only; storefront content was not changed.',
                $opportunity->review_state->value,
            ),
        );
    }
}
