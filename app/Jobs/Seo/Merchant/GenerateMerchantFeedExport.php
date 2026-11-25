<?php

declare(strict_types=1);

namespace App\Jobs\Seo\Merchant;

use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Domain\Seo\Merchant\Exceptions\MerchantFeedExportSnapshotChanged;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportGenerator;
use App\Domain\Seo\Merchant\Services\MerchantFeedExportLifecycle;
use App\Models\MerchantFeedExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class GenerateMerchantFeedExport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $exportId,
    ) {}

    public function uniqueId(): string
    {
        return 'merchant-feed-export:'
            .$this->exportId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [
            10,
            60,
            300,
        ];
    }

    public function handle(
        MerchantFeedExportLifecycle $lifecycle,
        MerchantFeedExportGenerator $generator,
    ): void {
        $export =
            MerchantFeedExport::query()
                ->find(
                    $this->exportId,
                );

        if ($export === null) {
            return;
        }

        if (
            $export->status ===
                MerchantFeedExportStatus::Completed
            || $export->status ===
                MerchantFeedExportStatus::Failed
        ) {
            return;
        }

        $export =
            $lifecycle->start(
                $export,
            );

        if (
            $export->status ===
            MerchantFeedExportStatus::Completed
        ) {
            return;
        }

        try {
            $file =
                $generator->generate(
                    $export,
                );
        } catch (
            MerchantFeedExportSnapshotChanged $exception
        ) {
            $lifecycle->fail(
                export: $export,

                failureCode: 'snapshot_changed',

                failureMessage: $exception->getMessage(),
            );

            return;
        }

        $lifecycle->complete(
            export: $export,

            file: $file,
        );
    }

    public function failed(
        Throwable $exception,
    ): void {
        $export =
            MerchantFeedExport::query()
                ->find(
                    $this->exportId,
                );

        if (
            $export === null
            || $export->status ===
                MerchantFeedExportStatus::Completed
            || $export->status ===
                MerchantFeedExportStatus::Failed
        ) {
            return;
        }

        try {
            app(
                MerchantFeedExportLifecycle::class,
            )->fail(
                export: $export,

                failureCode: 'export_job_failed',

                failureMessage: 'Merchant feed export generation failed after queue retries.',
            );
        } catch (Throwable) {
            // Preserve the original queue failure as the primary failure.
        }
    }
}
