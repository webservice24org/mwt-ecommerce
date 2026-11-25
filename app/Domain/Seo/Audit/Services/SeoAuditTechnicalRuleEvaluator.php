<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditFindingData;
use App\Domain\Seo\Audit\Data\SeoAuditTechnicalRuleEvaluationSummaryData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditImageHealthState;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditFinding;
use App\Models\SeoAuditImageResource;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

final readonly class SeoAuditTechnicalRuleEvaluator
{
    public const VERSION = 'seo-audit-technical-rules-v1';

    public const THIN_CATEGORY_WORD_THRESHOLD = 150;

    public const IMAGE_HEALTH_RULE_VERSION = 'seo-audit-image-health-v1';

    private const WRITE_CHUNK_SIZE = 500;

    public function __construct(
        private SeoAuditFindingFingerprint $fingerprints,
        private SeoAuditRuleRegistry $rules,
    ) {}

    public function evaluate(
        SeoAuditRun $run,
    ): SeoAuditTechnicalRuleEvaluationSummaryData {
        return DB::transaction(function () use ($run): SeoAuditTechnicalRuleEvaluationSummaryData {
            $current = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoAuditRunStatus::Running) {
                throw new DomainException(
                    'Technical SEO rules require a running audit run.',
                );
            }

            $pagesTotal = SeoAuditCrawlPage::query()
                ->where('seo_audit_run_id', $current->getKey())
                ->count();

            if ($pagesTotal > SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN) {
                throw new DomainException(
                    'SEO audit crawl page bound was exceeded.',
                );
            }

            $discoveredCount = SeoAuditCrawlPage::query()
                ->where('seo_audit_run_id', $current->getKey())
                ->where('state', SeoAuditCrawlState::Discovered)
                ->count();

            if ($discoveredCount > 0) {
                throw new DomainException(
                    'Technical SEO rules require a settled crawl inventory.',
                );
            }

            $linksTotal = DB::table('seo_audit_internal_links')
                ->where('seo_audit_run_id', $current->getKey())
                ->count();

            if ($linksTotal > SeoAuditCrawlInventoryRecorder::MAX_LINKS_PER_RUN) {
                throw new DomainException(
                    'SEO audit internal-link bound was exceeded.',
                );
            }

            $imagesTotal = DB::table('seo_audit_page_images')
                ->where('seo_audit_run_id', $current->getKey())
                ->count();

            if ($imagesTotal > SeoAuditPageImageRecorder::MAX_IMAGE_OBSERVATIONS_PER_RUN) {
                throw new DomainException(
                    'SEO audit image-observation bound was exceeded.',
                );
            }

            /** @var EloquentCollection<int, SeoAuditCrawlPage> $pages */
            $pages = SeoAuditCrawlPage::query()
                ->where('seo_audit_run_id', $current->getKey())
                ->orderBy('id')
                ->limit(SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN)
                ->get();

            /** @var array<string, SeoAuditCrawlPage> $pagesByUrl */
            $pagesByUrl = [];

            foreach ($pages as $page) {
                $pagesByUrl[$page->url] = $page;
            }

            $incomingFollowCounts = DB::table('seo_audit_internal_links')
                ->where('seo_audit_run_id', $current->getKey())
                ->where('nofollow', false)
                ->selectRaw('target_page_id, COUNT(*) AS aggregate')
                ->groupBy('target_page_id')
                ->pluck('aggregate', 'target_page_id');

            $observedImageResources = DB::table('seo_audit_page_images')
                ->where('seo_audit_run_id', $current->getKey())
                ->distinct()
                ->count('image_url_hash');

            $imageHealthResources = SeoAuditImageResource::query()
                ->where('seo_audit_run_id', $current->getKey())
                ->count();

            if (
                $imageHealthResources
                > SeoAuditImageHealthEnricher::MAX_IMAGE_RESOURCES_PER_RUN
            ) {
                throw new DomainException(
                    'SEO audit image-health resource bound was exceeded.',
                );
            }

            $imageHealthComplete =
                $observedImageResources === $imageHealthResources;

            $supportedRules = $this->supportedRules(
                imageHealthComplete: $imageHealthComplete,
            );

            SeoAuditFinding::query()
                ->where('seo_audit_run_id', $current->getKey())
                ->whereIn(
                    'rule_key',
                    array_map(
                        static fn (SeoAuditRule $rule): string => $rule->value,
                        $supportedRules,
                    ),
                )
                ->delete();

            /** @var list<array<string, mixed>> $buffer */
            $buffer = [];
            $findingsRecorded = 0;
            $errorCount = 0;
            $warningCount = 0;

            $emit = function (SeoAuditFindingData $finding) use (
                $current,
                &$buffer,
                &$findingsRecorded,
                &$errorCount,
                &$warningCount,
            ): void {
                if ($finding->severity !== $this->rules->defaultSeverity($finding->rule)) {
                    throw new DomainException(
                        'Technical SEO finding severity must match the deterministic rule registry.',
                    );
                }

                $now = now();

                $buffer[] = [
                    'seo_audit_run_id' => $current->getKey(),
                    'fingerprint' => $this->fingerprints->make($finding),
                    'rule_key' => $finding->rule->value,
                    'severity' => $finding->severity->value,
                    'subject_type' => $finding->subjectType->value,
                    'subject_key' => $finding->subjectKey,
                    'url' => $finding->url,
                    'source_url' => $finding->sourceUrl,
                    'message' => $finding->message,
                    'evidence' => json_encode(
                        $finding->evidence,
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
                    ),
                    'remediation' => $finding->remediation,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $findingsRecorded++;

                if ($finding->severity === SeoAuditSeverity::Error) {
                    $errorCount++;
                }

                if ($finding->severity === SeoAuditSeverity::Warning) {
                    $warningCount++;
                }

                if (count($buffer) >= self::WRITE_CHUNK_SIZE) {
                    $this->flush($buffer);
                    $buffer = [];
                }
            };

            foreach ($pages as $page) {
                $this->evaluatePage(
                    page: $page,
                    incomingFollowCount: (int) ($incomingFollowCounts[$page->id] ?? 0),
                    emit: $emit,
                );
            }

            $this->evaluateDuplicateCanonicals(
                pages: $pages,
                emit: $emit,
            );

            $this->evaluateDuplicateMetadata(
                pages: $pages,
                emit: $emit,
            );

            $this->evaluateCanonicalTargets(
                pages: $pages,
                pagesByUrl: $pagesByUrl,
                emit: $emit,
            );

            $this->evaluateRedirectChains(
                pages: $pages,
                pagesByUrl: $pagesByUrl,
                emit: $emit,
            );

            $brokenLinks = DB::table('seo_audit_internal_links as links')
                ->join(
                    'seo_audit_crawl_pages as sources',
                    'sources.id',
                    '=',
                    'links.source_page_id',
                )
                ->join(
                    'seo_audit_crawl_pages as targets',
                    'targets.id',
                    '=',
                    'links.target_page_id',
                )
                ->where('links.seo_audit_run_id', $current->getKey())
                ->where(function ($query): void {
                    $query
                        ->where('targets.state', SeoAuditCrawlState::Failed->value)
                        ->orWhere('targets.http_status', '>=', 400);
                })
                ->orderBy('links.id')
                ->limit(SeoAuditCrawlInventoryRecorder::MAX_LINKS_PER_RUN)
                ->get([
                    'links.edge_hash',
                    'links.target_url',
                    'links.anchor_text',
                    'links.occurrences',
                    'sources.url as source_url',
                    'targets.state as target_state',
                    'targets.http_status as target_status',
                    'targets.fetch_error_code as fetch_error_code',
                ]);

            foreach ($brokenLinks as $link) {
                /** @var object{edge_hash:string,target_url:string,anchor_text:string|null,occurrences:int|string,source_url:string,target_state:string,target_status:int|string|null,fetch_error_code:string|null} $link */
                $emit(new SeoAuditFindingData(
                    rule: SeoAuditRule::BrokenInternalLink,
                    severity: $this->rules->defaultSeverity(SeoAuditRule::BrokenInternalLink),
                    subjectType: SeoAuditSubjectType::Link,
                    subjectKey: (string) $link->edge_hash,
                    url: (string) $link->target_url,
                    sourceUrl: (string) $link->source_url,
                    message: 'Internal link points to a failed or non-success destination.',
                    evidence: [
                        'target_state' => (string) $link->target_state,
                        'status' => $link->target_status === null
                            ? null
                            : (int) $link->target_status,
                        'fetch_error_code' => $link->fetch_error_code === null
                            ? null
                            : (string) $link->fetch_error_code,
                        'anchor_text' => $link->anchor_text === null
                            ? null
                            : (string) $link->anchor_text,
                        'occurrences' => (int) $link->occurrences,
                    ],
                    remediation: 'Update or remove the broken internal link, or restore the destination URL.',
                ));
            }

            $missingAltImages = DB::table('seo_audit_page_images as images')
                ->join(
                    'seo_audit_crawl_pages as pages',
                    'pages.id',
                    '=',
                    'images.source_page_id',
                )
                ->where('images.seo_audit_run_id', $current->getKey())
                ->where('images.alt_attribute_present', false)
                ->orderBy('images.id')
                ->limit(SeoAuditPageImageRecorder::MAX_IMAGE_OBSERVATIONS_PER_RUN)
                ->get([
                    'images.observation_hash',
                    'images.image_url',
                    'images.occurrences',
                    'pages.url as source_url',
                ]);

            foreach ($missingAltImages as $image) {
                /** @var object{observation_hash:string,image_url:string,occurrences:int|string,source_url:string} $image */
                $emit(new SeoAuditFindingData(
                    rule: SeoAuditRule::MissingAlt,
                    severity: $this->rules->defaultSeverity(SeoAuditRule::MissingAlt),
                    subjectType: SeoAuditSubjectType::Image,
                    subjectKey: (string) $image->observation_hash,
                    url: (string) $image->image_url,
                    sourceUrl: (string) $image->source_url,
                    message: 'Image element is missing an alt attribute.',
                    evidence: [
                        'occurrences' => (int) $image->occurrences,
                    ],
                    remediation: 'Add useful alternative text, or an explicit empty alt attribute for a decorative image.',
                ));
            }

            if ($imageHealthComplete) {
                $brokenImages = DB::table('seo_audit_page_images as images')
                    ->join(
                        'seo_audit_image_resources as resources',
                        function ($join): void {
                            $join
                                ->on(
                                    'resources.seo_audit_run_id',
                                    '=',
                                    'images.seo_audit_run_id',
                                )
                                ->on(
                                    'resources.image_url_hash',
                                    '=',
                                    'images.image_url_hash',
                                );
                        },
                    )
                    ->join(
                        'seo_audit_crawl_pages as pages',
                        'pages.id',
                        '=',
                        'images.source_page_id',
                    )
                    ->where('images.seo_audit_run_id', $current->getKey())
                    ->where('resources.state', SeoAuditImageHealthState::Broken->value)
                    ->orderBy('images.id')
                    ->limit(SeoAuditPageImageRecorder::MAX_IMAGE_OBSERVATIONS_PER_RUN)
                    ->get([
                        'images.observation_hash',
                        'images.image_url',
                        'images.occurrences',
                        'pages.url as source_url',
                        'resources.http_status',
                        'resources.content_type',
                        'resources.failure_code',
                    ]);

                foreach ($brokenImages as $image) {
                    /** @var object{observation_hash:string,image_url:string,occurrences:int|string,source_url:string,http_status:int|string|null,content_type:string|null,failure_code:string|null} $image */
                    $emit(new SeoAuditFindingData(
                        rule: SeoAuditRule::BrokenImage,
                        severity: $this->rules->defaultSeverity(SeoAuditRule::BrokenImage),
                        subjectType: SeoAuditSubjectType::Image,
                        subjectKey: (string) $image->observation_hash,
                        url: (string) $image->image_url,
                        sourceUrl: (string) $image->source_url,
                        message: 'Image resource failed the bounded SEO audit health check.',
                        evidence: [
                            'status' => $image->http_status === null
                                ? null
                                : (int) $image->http_status,
                            'content_type' => $image->content_type === null
                                ? null
                                : (string) $image->content_type,
                            'failure_code' => $image->failure_code === null
                                ? null
                                : (string) $image->failure_code,
                            'occurrences' => (int) $image->occurrences,
                        ],
                        remediation: 'Restore the image resource or replace the image URL with a working image response.',
                    ));
                }
            }

            if ($buffer !== []) {
                $this->flush($buffer);
            }

            return new SeoAuditTechnicalRuleEvaluationSummaryData(
                rulesEvaluated: count($supportedRules),
                findingsRecorded: $findingsRecorded,
                errorCount: $errorCount,
                warningCount: $warningCount,
                pagesEvaluated: $pagesTotal,
                linksEvaluated: $linksTotal,
                imagesEvaluated: $imagesTotal,
                // N.4 compatibility fallback contract: deferredRules: [SeoAuditRule::BrokenImage]
                deferredRules: $imageHealthComplete
                    ? []
                    : [SeoAuditRule::BrokenImage],
            );
        });
    }

    /**
     * @param  callable(SeoAuditFindingData):void  $emit
     */
    private function evaluatePage(
        SeoAuditCrawlPage $page,
        int $incomingFollowCount,
        callable $emit,
    ): void {
        if (! $this->isIndexableHtmlPage($page)) {
            return;
        }

        $subjectKey = $page->subject_key ?? $page->url_hash;

        if ($page->canonical_url === null) {
            $emit($this->pageFinding(
                SeoAuditRule::IndexableMissingCanonical,
                $page,
                $subjectKey,
                'Indexable page does not declare a canonical URL.',
                [],
                'Publish one authoritative canonical URL for this indexable page.',
            ));
        }

        if ($page->title === null) {
            $emit($this->pageFinding(
                SeoAuditRule::IndexableMissingTitle,
                $page,
                $subjectKey,
                'Indexable page is missing a document title.',
                [],
                'Add a unique, descriptive title for this page.',
            ));
        }

        if ($page->meta_description === null) {
            $emit($this->pageFinding(
                SeoAuditRule::EmptyDescription,
                $page,
                $subjectKey,
                'Indexable page has no meta description content.',
                [],
                'Add a concise, page-specific meta description.',
            ));
        }

        if ($page->h1_count === 0) {
            $emit($this->pageFinding(
                SeoAuditRule::MissingH1,
                $page,
                $subjectKey,
                'Indexable page has no H1 heading.',
                ['h1_count' => 0],
                'Add one clear primary H1 heading that describes the page.',
            ));
        }

        if ($page->h1_count !== null && $page->h1_count > 1) {
            $emit($this->pageFinding(
                SeoAuditRule::MultipleH1,
                $page,
                $subjectKey,
                'Indexable page contains multiple H1 headings.',
                ['h1_count' => $page->h1_count],
                'Keep one intentional primary H1 and demote secondary headings where appropriate.',
            ));
        }

        if (
            $page->subject_type === SeoAuditSubjectType::Category
            && $page->word_count !== null
            && $page->word_count < self::THIN_CATEGORY_WORD_THRESHOLD
        ) {
            $emit($this->pageFinding(
                SeoAuditRule::ThinCategoryPage,
                $page,
                $subjectKey,
                'Indexable category page has very little visible text content.',
                [
                    'word_count' => $page->word_count,
                    'threshold' => self::THIN_CATEGORY_WORD_THRESHOLD,
                ],
                'Add useful category-specific copy that helps shoppers understand the products and intent of the page.',
            ));
        }

        if (
            $page->subject_type === SeoAuditSubjectType::Product
            && $incomingFollowCount === 0
        ) {
            $emit($this->pageFinding(
                SeoAuditRule::OrphanProduct,
                $page,
                $subjectKey,
                'Indexable product page has no followed internal links pointing to it.',
                ['incoming_follow_links' => 0],
                'Link to the product from relevant categories, navigation, recommendations, or editorial content.',
            ));
        }

        if (
            $page->subject_type === SeoAuditSubjectType::Category
            && $incomingFollowCount === 0
        ) {
            $emit($this->pageFinding(
                SeoAuditRule::OrphanCategory,
                $page,
                $subjectKey,
                'Indexable category page has no followed internal links pointing to it.',
                ['incoming_follow_links' => 0],
                'Link to the category from relevant navigation or parent/category discovery paths.',
            ));
        }
    }

    /**
     * @param  EloquentCollection<int, SeoAuditCrawlPage>  $pages
     * @param  callable(SeoAuditFindingData):void  $emit
     */
    private function evaluateDuplicateCanonicals(
        EloquentCollection $pages,
        callable $emit,
    ): void {
        /** @var array<string, list<SeoAuditCrawlPage>> $groups */
        $groups = [];

        foreach ($pages as $page) {
            if (
                ! $this->isIndexableHtmlPage($page)
                || $page->canonical_url === null
            ) {
                continue;
            }

            $groups[$page->canonical_url][] = $page;
        }

        foreach ($groups as $canonical => $group) {
            if (count($group) < 2) {
                continue;
            }

            $urls = array_map(
                static fn (SeoAuditCrawlPage $page): string => $page->url,
                $group,
            );
            sort($urls);

            foreach ($group as $page) {
                $emit($this->pageFinding(
                    SeoAuditRule::DuplicateCanonical,
                    $page,
                    $page->subject_key ?? $page->url_hash,
                    'Multiple indexable pages declare the same canonical target.',
                    [
                        'canonical_url' => $canonical,
                        'duplicate_urls' => $urls,
                    ],
                    'Confirm whether these URLs should consolidate; otherwise give each distinct page its correct canonical URL.',
                ));
            }
        }
    }

    /**
     * @param  EloquentCollection<int, SeoAuditCrawlPage>  $pages
     * @param  callable(SeoAuditFindingData):void  $emit
     */
    private function evaluateDuplicateMetadata(
        EloquentCollection $pages,
        callable $emit,
    ): void {
        /** @var array<string, list<SeoAuditCrawlPage>> $titleGroups */
        $titleGroups = [];
        /** @var array<string, list<SeoAuditCrawlPage>> $descriptionGroups */
        $descriptionGroups = [];

        foreach ($pages as $page) {
            if (! $this->isIndexableHtmlPage($page)) {
                continue;
            }

            if ($page->title !== null) {
                $titleGroups[$this->normalizeMetadata($page->title)][] = $page;
            }

            if ($page->meta_description !== null) {
                $descriptionGroups[$this->normalizeMetadata($page->meta_description)][] = $page;
            }
        }

        $this->emitDuplicateMetadataGroups(
            groups: $titleGroups,
            rule: SeoAuditRule::DuplicateTitle,
            field: 'title',
            emit: $emit,
        );

        $this->emitDuplicateMetadataGroups(
            groups: $descriptionGroups,
            rule: SeoAuditRule::DuplicateDescription,
            field: 'meta_description',
            emit: $emit,
        );
    }

    /**
     * @param  array<string, list<SeoAuditCrawlPage>>  $groups
     * @param  callable(SeoAuditFindingData):void  $emit
     */
    private function emitDuplicateMetadataGroups(
        array $groups,
        SeoAuditRule $rule,
        string $field,
        callable $emit,
    ): void {
        foreach ($groups as $normalizedValue => $group) {
            if ($normalizedValue === '' || count($group) < 2) {
                continue;
            }

            $urls = array_map(
                static fn (SeoAuditCrawlPage $page): string => $page->url,
                $group,
            );
            sort($urls);

            foreach ($group as $page) {
                $emit($this->pageFinding(
                    $rule,
                    $page,
                    $page->subject_key ?? $page->url_hash,
                    $rule === SeoAuditRule::DuplicateTitle
                        ? 'Indexable page shares its title with another indexable page.'
                        : 'Indexable page shares its meta description with another indexable page.',
                    [
                        'normalized_value' => $normalizedValue,
                        'duplicate_urls' => $urls,
                    ],
                    $field === 'title'
                        ? 'Write a unique title that reflects this page specifically.'
                        : 'Write a unique meta description that reflects this page specifically.',
                ));
            }
        }
    }

    /**
     * @param  EloquentCollection<int, SeoAuditCrawlPage>  $pages
     * @param  array<string, SeoAuditCrawlPage>  $pagesByUrl
     * @param  callable(SeoAuditFindingData):void  $emit
     */
    private function evaluateCanonicalTargets(
        EloquentCollection $pages,
        array $pagesByUrl,
        callable $emit,
    ): void {
        foreach ($pages as $page) {
            if (
                ! $this->isIndexableHtmlPage($page)
                || $page->canonical_url === null
            ) {
                continue;
            }

            $target = $pagesByUrl[$page->canonical_url] ?? null;

            if ($target === null) {
                continue;
            }

            $non200 = $target->state === SeoAuditCrawlState::Failed
                || $target->http_status === null
                || $target->http_status < 200
                || $target->http_status > 299;

            if (! $non200) {
                continue;
            }

            $emit($this->pageFinding(
                SeoAuditRule::CanonicalToNon200,
                $page,
                $page->subject_key ?? $page->url_hash,
                'Canonical URL points to an observed non-200 destination.',
                [
                    'canonical_url' => $page->canonical_url,
                    'target_state' => $target->state->value,
                    'target_status' => $target->http_status,
                    'target_error_code' => $target->fetch_error_code,
                ],
                'Point the canonical at a stable 200 response that represents the authoritative page.',
            ));
        }
    }

    /**
     * @param  EloquentCollection<int, SeoAuditCrawlPage>  $pages
     * @param  array<string, SeoAuditCrawlPage>  $pagesByUrl
     * @param  callable(SeoAuditFindingData):void  $emit
     */
    private function evaluateRedirectChains(
        EloquentCollection $pages,
        array $pagesByUrl,
        callable $emit,
    ): void {
        foreach ($pages as $page) {
            if (
                $page->redirect_url === null
                || $page->http_status === null
                || $page->http_status < 300
                || $page->http_status > 399
            ) {
                continue;
            }

            $target = $pagesByUrl[$page->redirect_url] ?? null;

            if (
                $target === null
                || $target->redirect_url === null
                || $target->http_status === null
                || $target->http_status < 300
                || $target->http_status > 399
            ) {
                continue;
            }

            $emit(new SeoAuditFindingData(
                rule: SeoAuditRule::RedirectChain,
                severity: $this->rules->defaultSeverity(SeoAuditRule::RedirectChain),
                subjectType: SeoAuditSubjectType::Url,
                subjectKey: $page->url_hash,
                url: $page->url,
                message: 'Redirect points to another redirect before reaching a final destination.',
                evidence: [
                    'first_status' => $page->http_status,
                    'first_target' => $page->redirect_url,
                    'second_status' => $target->http_status,
                    'second_target' => $target->redirect_url,
                ],
                remediation: 'Update the first redirect or internal links to point directly at the final destination.',
            ));
        }
    }

    /** @param array<string, mixed> $evidence */
    private function pageFinding(
        SeoAuditRule $rule,
        SeoAuditCrawlPage $page,
        string $subjectKey,
        string $message,
        array $evidence,
        string $remediation,
    ): SeoAuditFindingData {
        return new SeoAuditFindingData(
            rule: $rule,
            severity: $this->rules->defaultSeverity($rule),
            subjectType: $page->subject_type,
            subjectKey: $subjectKey,
            url: $page->url,
            message: $message,
            evidence: $evidence,
            remediation: $remediation,
        );
    }

    private function isIndexableHtmlPage(
        SeoAuditCrawlPage $page,
    ): bool {
        return $page->state === SeoAuditCrawlState::Fetched
            && $page->is_indexable === true
            && $page->http_status !== null
            && $page->http_status >= 200
            && $page->http_status <= 299;
    }

    private function normalizeMetadata(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return mb_strtolower($normalized ?? trim($value));
    }

    /** @return list<SeoAuditRule> */
    private function supportedRules(
        bool $imageHealthComplete,
    ): array {
        $rules = [
            SeoAuditRule::OrphanProduct,
            SeoAuditRule::OrphanCategory,
            SeoAuditRule::BrokenInternalLink,
            SeoAuditRule::RedirectChain,
            SeoAuditRule::DuplicateCanonical,
            SeoAuditRule::CanonicalToNon200,
            SeoAuditRule::IndexableMissingCanonical,
            SeoAuditRule::IndexableMissingTitle,
            SeoAuditRule::EmptyDescription,
            SeoAuditRule::DuplicateTitle,
            SeoAuditRule::DuplicateDescription,
            SeoAuditRule::MissingH1,
            SeoAuditRule::MultipleH1,
            SeoAuditRule::MissingAlt,
            SeoAuditRule::ThinCategoryPage,
        ];

        if ($imageHealthComplete) {
            $rules[] = SeoAuditRule::BrokenImage;
        }

        return $rules;
    }

    /** @param list<array<string, mixed>> $rows */
    private function flush(array $rows): void
    {
        SeoAuditFinding::query()->upsert(
            $rows,
            ['seo_audit_run_id', 'fingerprint'],
            [
                'rule_key',
                'severity',
                'subject_type',
                'subject_key',
                'url',
                'source_url',
                'message',
                'evidence',
                'remediation',
                'updated_at',
            ],
        );
    }
}
