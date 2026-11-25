<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Audit\Services\SeoAuditAdminRunStarter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\StartSeoAuditRunRequest;
use App\Models\Admin;
use App\Models\Product;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class SeoAuditRunController extends Controller
{
    public function store(
        StartSeoAuditRunRequest $request,
        SeoAuditAdminRunStarter $starter,
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
            $run = $starter->start($admin);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'audit' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'admin.seo.audits.index',
                [
                    'run' => $run->public_id,
                ],
            )
            ->with(
                'success',
                'SEO audit queued successfully.',
            );
    }
}
