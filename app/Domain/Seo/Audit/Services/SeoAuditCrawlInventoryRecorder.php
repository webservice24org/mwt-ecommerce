<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditCrawlPageData;
use App\Domain\Seo\Audit\Data\SeoAuditInternalLinkData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLink;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SeoAuditCrawlInventoryRecorder
{
    public const MAX_PAGES_PER_RUN = 5000;

    public const MAX_LINKS_PER_RUN = 50000;

    public const MAX_DEPTH = 12;

    public function __construct(
        private SeoAuditUrlNormalizer $urls,
    ) {}

    public function recordPage(
        SeoAuditRun $run,
        string $canonicalHost,
        SeoAuditCrawlPageData $data,
    ): SeoAuditCrawlPage {
        return DB::transaction(
            function () use (
                $run,
                $canonicalHost,
                $data,
            ): SeoAuditCrawlPage {
                $current = $this->lockedRun($run);

                $this->assertRunning($current);

                $normalizedUrl =
                    $this
                        ->urls
                        ->normalizeInternal(
                            reference: $data->url,
                            canonicalHost: $canonicalHost,
                        );

                if ($normalizedUrl === null) {
                    throw new DomainException(
                        'SEO audit crawl inventory only accepts internal HTTP(S) URLs.',
                    );
                }

                $this->validatePageData($data);

                return $this->upsertPage(
                    run: $current,
                    data: $data,
                    normalizedUrl: $normalizedUrl,
                );
            },
        );
    }

    public function recordLink(
        SeoAuditRun $run,
        string $canonicalHost,
        SeoAuditInternalLinkData $data,
    ): ?SeoAuditInternalLink {
        return DB::transaction(
            function () use (
                $run,
                $canonicalHost,
                $data,
            ): ?SeoAuditInternalLink {
                $current = $this->lockedRun($run);

                $this->assertRunning($current);
                $this->validateLinkData($data);

                $sourceUrl =
                    $this
                        ->urls
                        ->normalizeInternal(
                            reference: $data->sourceUrl,
                            canonicalHost: $canonicalHost,
                        );

                if ($sourceUrl === null) {
                    throw new DomainException(
                        'SEO audit internal-link sources must be internal HTTP(S) URLs.',
                    );
                }

                $targetUrl =
                    $this
                        ->urls
                        ->normalizeInternal(
                            reference: $data
                                ->targetReference,
                            canonicalHost: $canonicalHost,
                            baseUrl: $sourceUrl,
                        );

                /*
                 * Mailto/tel/javascript/data links and external
                 * destinations are outside the internal-link graph.
                 */
                if ($targetUrl === null) {
                    return null;
                }

                $sourcePage = $this->upsertPage(
                    run: $current,
                    data: new SeoAuditCrawlPageData(
                        url: $sourceUrl,
                        state: SeoAuditCrawlState::Discovered,
                        depth: $data
                            ->sourceDepth,
                    ),
                    normalizedUrl: $sourceUrl,
                );

                $targetDepth =
                    min(
                        self::MAX_DEPTH,
                        $data->sourceDepth + 1,
                    );

                $targetPage = $this->upsertPage(
                    run: $current,
                    data: new SeoAuditCrawlPageData(
                        url: $targetUrl,
                        state: SeoAuditCrawlState::Discovered,
                        depth: $targetDepth,
                    ),
                    normalizedUrl: $targetUrl,
                );

                $edgeHash = hash(
                    'sha256',
                    implode(
                        "\0",
                        [
                            $sourcePage
                                ->url_hash,
                            $targetPage
                                ->url_hash,
                            $data->nofollow
                                ? 'nofollow'
                                : 'follow',
                        ],
                    ),
                );

                $existing =
                    SeoAuditInternalLink::query()
                        ->where(
                            'seo_audit_run_id',
                            $current->getKey(),
                        )
                        ->where(
                            'edge_hash',
                            $edgeHash,
                        )
                        ->first();

                if (
                    $existing === null
                    && SeoAuditInternalLink::query()
                        ->where(
                            'seo_audit_run_id',
                            $current->getKey(),
                        )
                        ->count()
                        >= self::MAX_LINKS_PER_RUN
                ) {
                    throw new DomainException(
                        'SEO audit internal-link graph limit reached.',
                    );
                }

                $anchorText =
                    $this->trimNullable(
                        $data->anchorText,
                    );

                return SeoAuditInternalLink::query()
                    ->updateOrCreate(
                        [
                            'seo_audit_run_id' => $current
                                ->getKey(),
                            'edge_hash' => $edgeHash,
                        ],
                        [
                            'source_page_id' => $sourcePage
                                ->getKey(),
                            'target_page_id' => $targetPage
                                ->getKey(),
                            'target_url_hash' => $targetPage
                                ->url_hash,
                            'target_url' => $targetPage
                                ->url,
                            'anchor_text' => $anchorText,
                            'nofollow' => $data
                                ->nofollow,
                            'occurrences' => $data
                                ->occurrences,
                        ],
                    );
            },
        );
    }

    private function upsertPage(
        SeoAuditRun $run,
        SeoAuditCrawlPageData $data,
        string $normalizedUrl,
    ): SeoAuditCrawlPage {
        $urlHash =
            $this
                ->urls
                ->hash(
                    $normalizedUrl,
                );

        $existing =
            SeoAuditCrawlPage::query()
                ->where(
                    'seo_audit_run_id',
                    $run->getKey(),
                )
                ->where(
                    'url_hash',
                    $urlHash,
                )
                ->first();

        if (
            $existing === null
            && SeoAuditCrawlPage::query()
                ->where(
                    'seo_audit_run_id',
                    $run->getKey(),
                )
                ->count()
                >= self::MAX_PAGES_PER_RUN
        ) {
            throw new DomainException(
                'SEO audit crawl inventory limit reached.',
            );
        }

        $attributes = [
            'depth' => $existing === null
                    ? $data->depth
                    : min(
                        $existing->depth,
                        $data->depth,
                    ),
            'subject_type' => $this->subjectType(
                $existing,
                $data,
            ),
            'subject_key' => $this->subjectKey(
                $existing,
                $data,
            ),
        ];

        if (
            $existing === null
            || $data->state->rank()
                >= $existing
                    ->state
                    ->rank()
        ) {
            $attributes = [
                ...$attributes,
                ...$this->observationAttributes(
                    normalizedUrl: $normalizedUrl,
                    data: $data,
                ),
            ];
        }

        if ($existing === null) {
            return SeoAuditCrawlPage::query()
                ->create([
                    'seo_audit_run_id' => $run->getKey(),
                    'url_hash' => $urlHash,
                    'url' => $normalizedUrl,
                    ...$attributes,
                ]);
        }

        $existing
            ->forceFill($attributes)
            ->save();

        return $existing->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function observationAttributes(
        string $normalizedUrl,
        SeoAuditCrawlPageData $data,
    ): array {
        return [
            'state' => $data->state,
            'http_status' => $data->httpStatus,
            'content_type' => $this->trimNullable(
                $data
                    ->contentType,
            ),
            'redirect_url' => $this
                ->normalizeOptionalReference(
                    $data
                        ->redirectUrl,
                    $normalizedUrl,
                ),
            'canonical_url' => $this
                ->normalizeOptionalReference(
                    $data
                        ->canonicalUrl,
                    $normalizedUrl,
                ),
            'is_indexable' => $data
                ->isIndexable,
            'title' => $this->trimNullable(
                $data
                    ->title,
            ),
            'meta_description' => $this->trimNullable(
                $data
                    ->metaDescription,
            ),
            'h1_count' => $data
                ->h1Count,
            'word_count' => $data
                ->wordCount,
            'fetch_error_code' => $this->trimNullable(
                $data
                    ->fetchErrorCode,
            ),
            'fetch_error_message' => $this->trimNullable(
                $data
                    ->fetchErrorMessage,
            ),
            'fetched_at' => $data->state
                    === SeoAuditCrawlState::Discovered
                    ? null
                    : now(),
        ];
    }

    private function normalizeOptionalReference(
        ?string $reference,
        string $baseUrl,
    ): ?string {
        if ($reference === null) {
            return null;
        }

        return $this
            ->urls
            ->normalizeReference(
                reference: $reference,
                baseUrl: $baseUrl,
            );
    }

    private function subjectType(
        ?SeoAuditCrawlPage $existing,
        SeoAuditCrawlPageData $data,
    ): SeoAuditSubjectType {
        if (
            $existing === null
            || $data->subjectType
                !== SeoAuditSubjectType::Url
        ) {
            return $data->subjectType;
        }

        return $existing->subject_type;
    }

    private function subjectKey(
        ?SeoAuditCrawlPage $existing,
        SeoAuditCrawlPageData $data,
    ): ?string {
        $incoming =
            $this
                ->trimNullable(
                    $data
                        ->subjectKey,
                );

        if ($incoming !== null) {
            return $incoming;
        }

        return $existing
            ?->subject_key;
    }

    private function validatePageData(
        SeoAuditCrawlPageData $data,
    ): void {
        if (
            $data->state
                === SeoAuditCrawlState::Fetched
            && $data->httpStatus === null
        ) {
            throw new DomainException(
                'Fetched SEO audit pages require an HTTP status.',
            );
        }

        if (
            $data->state
                === SeoAuditCrawlState::Discovered
            && $this->hasResponseObservation(
                $data,
            )
        ) {
            throw new DomainException(
                'Discovered SEO audit pages cannot contain fetched response observations.',
            );
        }

        if (
            $data->depth < 0
            || $data->depth
                > self::MAX_DEPTH
        ) {
            throw new DomainException(
                'SEO audit crawl depth is outside the supported bound.',
            );
        }

        if (
            $data->httpStatus !== null
            && (
                $data->httpStatus < 100
                || $data->httpStatus > 599
            )
        ) {
            throw new DomainException(
                'SEO audit HTTP status must be between 100 and 599.',
            );
        }

        if (
            $data->h1Count !== null
            && $data->h1Count < 0
        ) {
            throw new DomainException(
                'SEO audit H1 count cannot be negative.',
            );
        }

        if (
            $data->wordCount !== null
            && $data->wordCount < 0
        ) {
            throw new DomainException(
                'SEO audit word count cannot be negative.',
            );
        }
    }

    private function hasResponseObservation(
        SeoAuditCrawlPageData $data,
    ): bool {
        return $data->httpStatus !== null
            || $data->contentType !== null
            || $data->redirectUrl !== null
            || $data->canonicalUrl !== null
            || $data->isIndexable !== null
            || $data->title !== null
            || $data->metaDescription !== null
            || $data->h1Count !== null
            || $data->wordCount !== null
            || $data->fetchErrorCode !== null
            || $data->fetchErrorMessage !== null;
    }

    private function validateLinkData(
        SeoAuditInternalLinkData $data,
    ): void {
        if (
            $data->sourceDepth < 0
            || $data->sourceDepth
                > self::MAX_DEPTH
        ) {
            throw new DomainException(
                'SEO audit link source depth is outside the supported bound.',
            );
        }

        if ($data->occurrences < 1) {
            throw new DomainException(
                'SEO audit link occurrence count must be positive.',
            );
        }

        if ($data->occurrences > 65535) {
            throw new DomainException(
                'SEO audit link occurrence count is too large.',
            );
        }

        if (
            $data->anchorText !== null
            && mb_strlen(
                $data->anchorText,
            ) > 500
        ) {
            throw new DomainException(
                'SEO audit link anchor text is too long.',
            );
        }
    }

    private function assertRunning(
        SeoAuditRun $run,
    ): void {
        if (
            $run->status
            !== SeoAuditRunStatus::Running
        ) {
            throw new DomainException(
                'SEO audit crawl inventory may only change while the run is active.',
            );
        }
    }

    private function lockedRun(
        SeoAuditRun $run,
    ): SeoAuditRun {
        return SeoAuditRun::query()
            ->lockForUpdate()
            ->findOrFail(
                $run->getKey(),
            );
    }

    private function trimNullable(
        ?string $value,
    ): ?string {
        return $value === null
            ? null
            : trim($value);
    }
}
