<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditCrawlExecutionSummaryData;
use App\Domain\Seo\Audit\Data\SeoAuditCrawlPageData;
use App\Domain\Seo\Audit\Data\SeoAuditHtmlLinkObservationData;
use App\Domain\Seo\Audit\Data\SeoAuditInternalLinkData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Exceptions\SeoAuditFetchException;
use App\Domain\Seo\Audit\Queries\SeoAuditCrawlInventorySummaryQuery;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditPageImage;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Http\Client\ConnectionException;

final readonly class SeoAuditBoundedCrawler
{
    public const MAX_FETCHES_PER_EXECUTION = 5000;

    public const MAX_SEEDS = 50;

    public function __construct(
        private SeoAuditCrawlInventoryRecorder $inventory,
        private SeoAuditCrawlInventorySummaryQuery $summary,
        private SeoAuditHttpPageFetcher $fetcher,
        private SeoAuditHtmlObservationParser $html,
        private SeoAuditPageImageRecorder $images,
        private SeoAuditUrlNormalizer $urls,
    ) {}

    /**
     * @param  list<string>  $seedReferences
     */
    public function execute(
        SeoAuditRun $run,
        string $canonicalHost,
        array $seedReferences = ['/'],
        ?int $maxFetches = null,
    ): SeoAuditCrawlExecutionSummaryData {
        $current = SeoAuditRun::query()
            ->findOrFail(
                $run->getKey(),
            );

        if (
            $current->status
            !== SeoAuditRunStatus::Running
        ) {
            throw new DomainException(
                'SEO audit crawl execution requires a running audit run.',
            );
        }

        if (
            $seedReferences === []
            || count($seedReferences) > self::MAX_SEEDS
        ) {
            throw new DomainException(
                'SEO audit crawl seeds are outside the supported bound.',
            );
        }

        $fetchLimit =
            $maxFetches
            ?? self::MAX_FETCHES_PER_EXECUTION;

        if (
            $fetchLimit < 1
            || $fetchLimit > self::MAX_FETCHES_PER_EXECUTION
        ) {
            throw new DomainException(
                'SEO audit crawl fetch limit is outside the supported bound.',
            );
        }

        foreach ($seedReferences as $seedReference) {
            $this->inventory->recordPage(
                run: $current,
                canonicalHost: $canonicalHost,
                data: new SeoAuditCrawlPageData(
                    url: $seedReference,
                    state: SeoAuditCrawlState::Discovered,
                    depth: 0,
                ),
            );
        }

        $fetches = 0;

        while (
            $fetches
            < $fetchLimit
        ) {
            $page = SeoAuditCrawlPage::query()
                ->where(
                    'seo_audit_run_id',
                    $current->getKey(),
                )
                ->where(
                    'state',
                    SeoAuditCrawlState::Discovered,
                )
                ->where(
                    'depth',
                    '<=',
                    SeoAuditCrawlInventoryRecorder::MAX_DEPTH,
                )
                ->orderBy('depth')
                ->orderBy('id')
                ->first();

            if ($page === null) {
                break;
            }

            $fetches++;

            $this->fetchPage(
                run: $current,
                page: $page,
                canonicalHost: $canonicalHost,
            );
        }

        $inventorySummary =
            $this
                ->summary
                ->get($current);

        return new SeoAuditCrawlExecutionSummaryData(
            pagesTotal: $inventorySummary
                ->pagesTotal,
            fetchedCount: $inventorySummary
                ->fetchedCount,
            failedCount: $inventorySummary
                ->failedCount,
            discoveredCount: $inventorySummary
                ->discoveredCount,
            linksTotal: $inventorySummary
                ->linksTotal,
            imagesTotal: SeoAuditPageImage::query()
                ->where(
                    'seo_audit_run_id',
                    $current->getKey(),
                )
                ->count(),
            fetchesAttempted: $fetches,
        );
    }

    private function fetchPage(
        SeoAuditRun $run,
        SeoAuditCrawlPage $page,
        string $canonicalHost,
    ): void {
        try {
            $result =
                $this
                    ->fetcher
                    ->fetch(
                        url: $page->url,
                        canonicalHost: $canonicalHost,
                    );
        } catch (ConnectionException) {
            $this->recordFailure(
                run: $run,
                page: $page,
                canonicalHost: $canonicalHost,
                code: 'connection_error',
                message: 'SEO audit could not connect to the page.',
            );

            return;
        } catch (SeoAuditFetchException $exception) {
            $this->recordFailure(
                run: $run,
                page: $page,
                canonicalHost: $canonicalHost,
                code: $exception
                    ->errorCode,
                message: $exception
                    ->getMessage(),
            );

            return;
        }

        if ($result->isRedirect()) {
            $this->recordRedirect(
                run: $run,
                page: $page,
                canonicalHost: $canonicalHost,
                status: $result->status,
                contentType: $result->contentType,
                location: $result
                    ->redirectLocation,
            );

            return;
        }

        if (
            ! $result->isHtml()
            || $result->body === null
        ) {
            $this->inventory->recordPage(
                run: $run,
                canonicalHost: $canonicalHost,
                data: $this->pageData(
                    page: $page,
                    state: SeoAuditCrawlState::Fetched,
                    httpStatus: $result->status,
                    contentType: $result->contentType,
                    isIndexable: false,
                ),
            );

            return;
        }

        $observation =
            $this
                ->html
                ->parse(
                    $result->body,
                );

        $xRobotsNoindex =
            $this->hasNoindexToken(
                $result
                    ->xRobotsTag,
            );

        $recordedPage =
            $this->inventory->recordPage(
                run: $run,
                canonicalHost: $canonicalHost,
                data: $this->pageData(
                    page: $page,
                    state: SeoAuditCrawlState::Fetched,
                    httpStatus: $result->status,
                    contentType: $result->contentType,
                    canonicalUrl: $observation
                        ->canonicalReference,
                    isIndexable: $result->status >= 200
                        && $result->status <= 299
                        && ! $observation
                            ->metaNoindex
                        && ! $xRobotsNoindex,
                    title: $observation
                        ->title,
                    metaDescription: $observation
                        ->metaDescription,
                    h1Count: $observation
                        ->h1Count,
                    wordCount: $observation
                        ->wordCount,
                ),
            );

        foreach ($observation->images as $image) {
            $this->images->record(
                run: $run,
                sourcePage: $recordedPage,
                data: $image,
            );
        }

        if (
            $page->depth
            >= SeoAuditCrawlInventoryRecorder::MAX_DEPTH
        ) {
            return;
        }

        foreach (
            $this->groupInternalLinks(
                links: $observation->links,
                pageUrl: $page->url,
                canonicalHost: $canonicalHost,
            ) as $link
        ) {
            $this->inventory->recordLink(
                run: $run,
                canonicalHost: $canonicalHost,
                data: new SeoAuditInternalLinkData(
                    sourceUrl: $page->url,
                    targetReference: $link['target'],
                    sourceDepth: $page->depth,
                    anchorText: $link['anchor'],
                    nofollow: $link['nofollow'],
                    occurrences: $link['occurrences'],
                ),
            );
        }
    }

    private function recordRedirect(
        SeoAuditRun $run,
        SeoAuditCrawlPage $page,
        string $canonicalHost,
        int $status,
        ?string $contentType,
        ?string $location,
    ): void {
        $this->inventory->recordPage(
            run: $run,
            canonicalHost: $canonicalHost,
            data: $this->pageData(
                page: $page,
                state: SeoAuditCrawlState::Fetched,
                httpStatus: $status,
                contentType: $contentType,
                redirectUrl: $location,
                isIndexable: false,
            ),
        );

        if (
            $location === null
            || $page->depth
                >= SeoAuditCrawlInventoryRecorder::MAX_DEPTH
        ) {
            return;
        }

        $target =
            $this
                ->urls
                ->normalizeInternal(
                    reference: $location,
                    canonicalHost: $canonicalHost,
                    baseUrl: $page->url,
                );

        if ($target === null) {
            return;
        }

        $this->inventory->recordPage(
            run: $run,
            canonicalHost: $canonicalHost,
            data: new SeoAuditCrawlPageData(
                url: $target,
                state: SeoAuditCrawlState::Discovered,
                depth: $page->depth + 1,
            ),
        );
    }

    private function recordFailure(
        SeoAuditRun $run,
        SeoAuditCrawlPage $page,
        string $canonicalHost,
        string $code,
        string $message,
    ): void {
        $this->inventory->recordPage(
            run: $run,
            canonicalHost: $canonicalHost,
            data: $this->pageData(
                page: $page,
                state: SeoAuditCrawlState::Failed,
                fetchErrorCode: $code,
                fetchErrorMessage: $message,
            ),
        );
    }

    private function pageData(
        SeoAuditCrawlPage $page,
        SeoAuditCrawlState $state,
        ?int $httpStatus = null,
        ?string $contentType = null,
        ?string $redirectUrl = null,
        ?string $canonicalUrl = null,
        ?bool $isIndexable = null,
        ?string $title = null,
        ?string $metaDescription = null,
        ?int $h1Count = null,
        ?int $wordCount = null,
        ?string $fetchErrorCode = null,
        ?string $fetchErrorMessage = null,
    ): SeoAuditCrawlPageData {
        return new SeoAuditCrawlPageData(
            url: $page->url,
            state: $state,
            depth: $page->depth,
            subjectType: $page->subject_type,
            subjectKey: $page->subject_key,
            httpStatus: $httpStatus,
            contentType: $contentType,
            redirectUrl: $redirectUrl,
            canonicalUrl: $canonicalUrl,
            isIndexable: $isIndexable,
            title: $title,
            metaDescription: $metaDescription,
            h1Count: $h1Count,
            wordCount: $wordCount,
            fetchErrorCode: $fetchErrorCode,
            fetchErrorMessage: $fetchErrorMessage,
        );
    }

    private function hasNoindexToken(
        ?string $value,
    ): bool {
        if ($value === null) {
            return false;
        }

        $tokens = preg_split(
            '/[\s,;:]+/',
            strtolower($value),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        return is_array($tokens)
            && in_array(
                'noindex',
                $tokens,
                true,
            );
    }

    /**
     * @param  list<SeoAuditHtmlLinkObservationData>  $links
     * @return list<array{target:string,anchor:string|null,nofollow:bool,occurrences:int}>
     */
    private function groupInternalLinks(
        array $links,
        string $pageUrl,
        string $canonicalHost,
    ): array {
        /** @var array<string, array{target:string,anchor:string|null,nofollow:bool,occurrences:int}> $grouped */
        $grouped = [];

        foreach ($links as $link) {
            $target =
                $this
                    ->urls
                    ->normalizeInternal(
                        reference: $link
                            ->reference,
                        canonicalHost: $canonicalHost,
                        baseUrl: $pageUrl,
                    );

            if ($target === null) {
                continue;
            }

            $key = hash(
                'sha256',
                $target
                ."\0"
                .($link->nofollow
                    ? 'nofollow'
                    : 'follow'),
            );

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'target' => $target,
                    'anchor' => $link->anchorText,
                    'nofollow' => $link->nofollow,
                    'occurrences' => 0,
                ];
            }

            $grouped[$key]['occurrences']++;

            if (
                $grouped[$key]['anchor'] === null
                && $link->anchorText !== null
            ) {
                $grouped[$key]['anchor'] =
                    $link->anchorText;
            }
        }

        return array_values($grouped);
    }
}
