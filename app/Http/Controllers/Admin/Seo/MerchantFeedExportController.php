<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportDownload;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportQueue;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\MerchantFeedExport;
use App\Models\Product;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MerchantFeedExportController extends Controller
{
    public function store(
        Request $request,
        MerchantFeedExportQueue $queue,
    ): RedirectResponse {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        $admin =
            $this->admin(
                $request,
            );

        $queue->requestAndDispatch(
            $admin,
        );

        return back()->with(
            'success',
            'Merchant feed export queued successfully.',
        );
    }

    public function retry(
        Request $request,
        MerchantFeedExport $export,
        MerchantFeedExportQueue $queue,
    ): RedirectResponse {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        if (
            $export->status !==
            MerchantFeedExportStatus::Failed
        ) {
            abort(
                409,
                'Only failed merchant feed exports may be retried.',
            );
        }

        try {
            $queue->retryAndDispatch(
                export: $export,

                admin: $this->admin(
                    $request,
                ),
            );
        } catch (DomainException) {
            abort(
                409,
                'Merchant feed export can no longer be retried.',
            );
        }

        return back()->with(
            'success',
            'Merchant feed export queued for retry.',
        );
    }

    public function download(
        Request $request,
        MerchantFeedExport $export,
        MerchantFeedExportDownload $download,
    ): StreamedResponse {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        try {
            return $download->response(
                export: $export,

                admin: $this->admin(
                    $request,
                ),
            );
        } catch (DomainException) {
            abort(
                409,
                'Merchant feed export is not available for download.',
            );
        } catch (RuntimeException) {
            abort(
                409,
                'Merchant feed export file is unavailable or failed integrity verification.',
            );
        }
    }

    private function admin(
        Request $request,
    ): Admin {
        $admin =
            $request->user(
                'admin',
            );

        if (! $admin instanceof Admin) {
            abort(
                403,
            );
        }

        return $admin;
    }
}
