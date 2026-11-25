<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditHtmlImageObservationData;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditPageImage;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SeoAuditPageImageRecorder
{
    public const MAX_IMAGE_OBSERVATIONS_PER_RUN = 50000;

    public function __construct(
        private SeoAuditUrlNormalizer $urls,
    ) {}

    public function record(
        SeoAuditRun $run,
        SeoAuditCrawlPage $sourcePage,
        SeoAuditHtmlImageObservationData $data,
    ): ?SeoAuditPageImage {
        return DB::transaction(
            function () use (
                $run,
                $sourcePage,
                $data,
            ): ?SeoAuditPageImage {
                $current = SeoAuditRun::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $run->getKey(),
                    );

                if (
                    $current->status
                    !== SeoAuditRunStatus::Running
                ) {
                    throw new DomainException(
                        'SEO audit image observations may only change while the run is active.',
                    );
                }

                if (
                    $sourcePage->seo_audit_run_id
                    !== $current->getKey()
                ) {
                    throw new DomainException(
                        'SEO audit image source page belongs to another run.',
                    );
                }

                if (
                    $data->occurrences < 1
                    || $data->occurrences > 65535
                ) {
                    throw new DomainException(
                        'SEO audit image occurrence count is outside the supported bound.',
                    );
                }

                $imageUrl =
                    $this
                        ->urls
                        ->normalizeReference(
                            reference: $data
                                ->reference,
                            baseUrl: $sourcePage
                                ->url,
                        );

                if ($imageUrl === null) {
                    return null;
                }

                $altText =
                    $data->altText === null
                        ? null
                        : trim(
                            $data->altText,
                        );

                $imageHash = hash(
                    'sha256',
                    $imageUrl,
                );

                $observationHash = hash(
                    'sha256',
                    implode(
                        "\0",
                        [
                            $imageHash,
                            $data->altAttributePresent
                                ? '1'
                                : '0',
                            $altText ?? '',
                        ],
                    ),
                );

                $existing =
                    SeoAuditPageImage::query()
                        ->where(
                            'seo_audit_run_id',
                            $current->getKey(),
                        )
                        ->where(
                            'source_page_id',
                            $sourcePage->getKey(),
                        )
                        ->where(
                            'observation_hash',
                            $observationHash,
                        )
                        ->first();

                if (
                    $existing === null
                    && SeoAuditPageImage::query()
                        ->where(
                            'seo_audit_run_id',
                            $current->getKey(),
                        )
                        ->count()
                        >= self::MAX_IMAGE_OBSERVATIONS_PER_RUN
                ) {
                    throw new DomainException(
                        'SEO audit image observation limit reached.',
                    );
                }

                return SeoAuditPageImage::query()
                    ->updateOrCreate(
                        [
                            'seo_audit_run_id' => $current
                                ->getKey(),
                            'source_page_id' => $sourcePage
                                ->getKey(),
                            'observation_hash' => $observationHash,
                        ],
                        [
                            'image_url_hash' => $imageHash,
                            'image_url' => $imageUrl,
                            'alt_attribute_present' => $data
                                ->altAttributePresent,
                            'alt_text' => $altText,
                            'occurrences' => $data
                                ->occurrences,
                        ],
                    );
            },
        );
    }
}
