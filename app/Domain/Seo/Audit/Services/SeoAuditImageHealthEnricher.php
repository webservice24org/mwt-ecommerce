<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditImageHealthChunkSummaryData;
use App\Domain\Seo\Audit\Data\SeoAuditImageHealthSummaryData;
use App\Domain\Seo\Audit\Enums\SeoAuditImageHealthState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Models\SeoAuditImageResource;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final readonly class SeoAuditImageHealthEnricher
{
    public const MAX_IMAGE_RESOURCES_PER_RUN = 5000;

    private const WRITE_CHUNK_SIZE = 500;

    public function __construct(
        private SeoAuditHttpImageHealthChecker $checker,
    ) {}

    public function enrich(
        SeoAuditRun $run,
        string $canonicalHost,
    ): SeoAuditImageHealthSummaryData {
        $current = SeoAuditRun::query()->findOrFail($run->getKey());

        if ($current->status !== SeoAuditRunStatus::Running) {
            throw new DomainException(
                'Image health may only be enriched while the SEO audit run is active.',
            );
        }

        $observations = DB::table('seo_audit_page_images')
            ->where('seo_audit_run_id', $current->getKey())
            ->count();

        $resources = DB::table('seo_audit_page_images')
            ->where('seo_audit_run_id', $current->getKey())
            ->select(['image_url_hash', 'image_url'])
            ->distinct()
            ->orderBy('image_url_hash')
            ->limit(self::MAX_IMAGE_RESOURCES_PER_RUN + 1)
            ->get();

        if ($resources->count() > self::MAX_IMAGE_RESOURCES_PER_RUN) {
            throw new DomainException(
                'SEO audit image health resource limit reached.',
            );
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = [];
        $healthy = 0;
        $broken = 0;
        $redirected = 0;
        $skippedExternal = 0;
        $checkedAt = now();

        foreach ($resources as $resource) {
            /** @var object{image_url_hash:string,image_url:string} $resource */
            $result = $this->checker->check(
                imageUrl: (string) $resource->image_url,
                canonicalHost: $canonicalHost,
            );

            match ($result->state) {
                SeoAuditImageHealthState::Healthy => $healthy++,
                SeoAuditImageHealthState::Broken => $broken++,
                SeoAuditImageHealthState::Redirected => $redirected++,
                SeoAuditImageHealthState::SkippedExternal => $skippedExternal++,
            };

            $rows[] = [
                'seo_audit_run_id' => $current->getKey(),
                'image_url_hash' => (string) $resource->image_url_hash,
                'image_url' => (string) $resource->image_url,
                'state' => $result->state->value,
                'http_status' => $result->httpStatus,
                'content_type' => $result->contentType,
                'failure_code' => $result->failureCode,
                'failure_message' => $result->failureMessage,
                'checked_at' => $checkedAt,
                'created_at' => $checkedAt,
                'updated_at' => $checkedAt,
            ];
        }

        DB::transaction(function () use ($current, $rows): void {
            $locked = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($current->getKey());

            if ($locked->status !== SeoAuditRunStatus::Running) {
                throw new DomainException(
                    'SEO audit run changed state while image health was being enriched.',
                );
            }

            SeoAuditImageResource::query()
                ->where('seo_audit_run_id', $locked->getKey())
                ->delete();

            foreach (array_chunk($rows, self::WRITE_CHUNK_SIZE) as $chunk) {
                SeoAuditImageResource::query()->insert($chunk);
            }
        });

        return new SeoAuditImageHealthSummaryData(
            observations: $observations,
            resources: $resources->count(),
            healthy: $healthy,
            broken: $broken,
            redirected: $redirected,
            skippedExternal: $skippedExternal,
        );
    }

    public function enrichChunk(
        SeoAuditRun $run,
        string $canonicalHost,
        int $maxResources,
    ): SeoAuditImageHealthChunkSummaryData {
        if (
            $maxResources < 1
            || $maxResources > self::MAX_IMAGE_RESOURCES_PER_RUN
        ) {
            throw new DomainException(
                'SEO audit image-health chunk size is outside the supported bound.',
            );
        }

        $current = SeoAuditRun::query()->findOrFail($run->getKey());

        if ($current->status !== SeoAuditRunStatus::Running) {
            throw new DomainException(
                'Image health may only be enriched while the SEO audit run is active.',
            );
        }

        $observations = DB::table('seo_audit_page_images')
            ->where('seo_audit_run_id', $current->getKey())
            ->count();

        $resourcesTotal = DB::table('seo_audit_page_images')
            ->where('seo_audit_run_id', $current->getKey())
            ->distinct()
            ->count('image_url_hash');

        if ($resourcesTotal > self::MAX_IMAGE_RESOURCES_PER_RUN) {
            throw new DomainException(
                'SEO audit image health resource limit reached.',
            );
        }

        $resources = DB::table('seo_audit_page_images as images')
            ->leftJoin(
                'seo_audit_image_resources as health',
                function (JoinClause $join) use ($current): void {
                    $join
                        ->on(
                            'health.image_url_hash',
                            '=',
                            'images.image_url_hash',
                        )
                        ->where(
                            'health.seo_audit_run_id',
                            '=',
                            $current->getKey(),
                        );
                },
            )
            ->where(
                'images.seo_audit_run_id',
                $current->getKey(),
            )
            ->whereNull('health.id')
            ->select([
                'images.image_url_hash',
                'images.image_url',
            ])
            ->distinct()
            ->orderBy('images.image_url_hash')
            ->limit($maxResources)
            ->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = [];
        $checkedAt = now();

        foreach ($resources as $resource) {
            /** @var object{image_url_hash:string,image_url:string} $resource */
            $result = $this->checker->check(
                imageUrl: (string) $resource->image_url,
                canonicalHost: $canonicalHost,
            );

            $rows[] = [
                'seo_audit_run_id' => $current->getKey(),
                'image_url_hash' => (string) $resource->image_url_hash,
                'image_url' => (string) $resource->image_url,
                'state' => $result->state->value,
                'http_status' => $result->httpStatus,
                'content_type' => $result->contentType,
                'failure_code' => $result->failureCode,
                'failure_message' => $result->failureMessage,
                'checked_at' => $checkedAt,
                'created_at' => $checkedAt,
                'updated_at' => $checkedAt,
            ];
        }

        DB::transaction(function () use ($current, $rows): void {
            $locked = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($current->getKey());

            if ($locked->status !== SeoAuditRunStatus::Running) {
                throw new DomainException(
                    'SEO audit run changed state while image health was being enriched.',
                );
            }

            foreach ($rows as $row) {
                SeoAuditImageResource::query()->updateOrCreate(
                    [
                        'seo_audit_run_id' => $locked->getKey(),
                        'image_url_hash' => $row['image_url_hash'],
                    ],
                    $row,
                );
            }
        });

        $observedResources = SeoAuditImageResource::query()
            ->where('seo_audit_run_id', $current->getKey())
            ->count();

        return new SeoAuditImageHealthChunkSummaryData(
            observations: $observations,
            resourcesTotal: $resourcesTotal,
            observedResources: $observedResources,
            processedThisPass: $resources->count(),
            remainingResources: max(
                0,
                $resourcesTotal - $observedResources,
            ),
        );
    }
}
