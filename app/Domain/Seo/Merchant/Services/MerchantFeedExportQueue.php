<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Enums\MerchantFeedExportFormat;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Jobs\Seo\Merchant\GenerateMerchantFeedExport;
use App\Models\Admin;
use App\Models\MerchantFeedExport;
use DomainException;
use Throwable;

final readonly class MerchantFeedExportQueue
{
    public function __construct(
        private MerchantFeedExportLifecycle $lifecycle,
    ) {}

    public function requestAndDispatch(
        Admin $admin,
        MerchantFeedExportFormat $format = MerchantFeedExportFormat::Tsv,
    ): MerchantFeedExport {
        $export =
            $this->lifecycle
                ->request(
                    admin: $admin,

                    format: $format,
                );

        $this->dispatch(
            $export,
        );

        return $export;
    }

    public function retryAndDispatch(
        MerchantFeedExport $export,
        Admin $admin,
    ): MerchantFeedExport {
        $queued =
            $this->lifecycle
                ->retry(
                    export: $export,

                    admin: $admin,
                );

        $this->dispatch(
            $queued,
        );

        return $queued;
    }

    public function dispatch(
        MerchantFeedExport $export,
    ): void {
        $export->refresh();

        if (
            $export->status !==
            MerchantFeedExportStatus::Queued
        ) {
            throw new DomainException(
                'Only queued merchant feed exports may be dispatched.',
            );
        }

        $queue =
            trim(
                (string) config(
                    'merchant-feed.exports.queue',
                    'default',
                ),
            );

        if ($queue === '') {
            $queue =
                'default';
        }

        try {
            GenerateMerchantFeedExport::dispatch(
                $export->id,
            )->onQueue(
                $queue,
            );
        } catch (Throwable $exception) {
            $this->lifecycle
                ->fail(
                    export: $export,

                    failureCode: 'queue_dispatch_failed',

                    failureMessage: 'Merchant feed export could not be dispatched to the queue.',
                );

            throw $exception;
        }
    }
}
