<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Queries;

use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\AiSeoAnalysis;
use App\Models\AiSeoAnalysisEnrichment;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class GetSeoTechnicalHealthQuery
{
    private const ANALYSIS_FRESH_DAYS =
        30;

    public function __construct(
        private SeoSettingsService $seoSettings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $settings =
            $this
                ->seoSettings
                ->get();

        $latestAnalyses =
            $this
                ->latestAnalyses();

        $coverage =
            $this
                ->coverage(
                    $latestAnalyses,
                );

        $freshness =
            $this
                ->analysisFreshness(
                    $latestAnalyses,
                );

        $checks = [
            $this->check(
                id: 'canonical_https',

                label: 'Canonical host uses HTTPS',

                category: 'discovery',

                maxScore: 15,

                status: $this->httpsStatus(
                    $settings
                        ->canonicalHost,
                ),

                detail: $settings
                    ->canonicalHost
                    ?? 'Canonical host is not configured.',

                recommendation: 'Configure one authoritative HTTPS canonical host.',
            ),

            $this->check(
                id: 'default_robots_index',

                label: 'Default robots index enabled',

                category: 'discovery',

                maxScore: 8,

                status: $settings
                    ->defaultRobotsIndex
                        ? 'pass'
                        : 'warn',

                detail: $settings
                    ->defaultRobotsIndex
                        ? 'New entities inherit index by default.'
                        : 'New entities inherit noindex unless overridden.',

                recommendation: 'Use noindex defaults only when intentionally required by the storefront strategy.',
            ),

            $this->check(
                id: 'default_robots_follow',

                label: 'Default robots follow enabled',

                category: 'discovery',

                maxScore: 5,

                status: $settings
                    ->defaultRobotsFollow
                        ? 'pass'
                        : 'warn',

                detail: $settings
                    ->defaultRobotsFollow
                        ? 'New entities inherit follow by default.'
                        : 'New entities inherit nofollow unless overridden.',

                recommendation: 'Keep follow enabled globally unless a deliberate crawl policy requires otherwise.',
            ),

            $this->check(
                id: 'sitemap_enabled',

                label: 'XML sitemap enabled',

                category: 'discovery',

                maxScore: 10,

                status: $settings
                    ->enableSitemap
                        ? 'pass'
                        : 'fail',

                detail: $settings
                    ->enableSitemap
                        ? 'Storefront sitemap generation is enabled.'
                        : 'Storefront sitemap generation is disabled.',

                recommendation: 'Enable XML sitemap generation for discoverable public content.',
            ),

            $this->check(
                id: 'structured_data_enabled',

                label: 'Structured data enabled',

                category: 'structured_identity',

                maxScore: 10,

                status: $settings
                    ->enableStructuredData
                        ? 'pass'
                        : 'fail',

                detail: $settings
                    ->enableStructuredData
                        ? 'Global structured-data rendering is enabled.'
                        : 'Global structured-data rendering is disabled.',

                recommendation: 'Enable server-authoritative structured data unless it is intentionally disabled.',
            ),

            $this->check(
                id: 'open_graph_enabled',

                label: 'Open Graph enabled',

                category: 'presentation',

                maxScore: 8,

                status: $settings
                    ->enableOpenGraph
                        ? 'pass'
                        : 'warn',

                detail: $settings
                    ->enableOpenGraph
                        ? 'Open Graph metadata rendering is enabled.'
                        : 'Open Graph metadata rendering is disabled.',

                recommendation: 'Enable Open Graph metadata for reliable social previews.',
            ),

            $this->check(
                id: 'twitter_cards_enabled',

                label: 'X/Twitter cards enabled',

                category: 'presentation',

                maxScore: 6,

                status: $settings
                    ->enableTwitterCards
                        ? 'pass'
                        : 'warn',

                detail: $settings
                    ->enableTwitterCards
                        ? 'X/Twitter card rendering is enabled.'
                        : 'X/Twitter card rendering is disabled.',

                recommendation: 'Enable X/Twitter cards when social sharing is part of the storefront strategy.',
            ),

            $this->check(
                id: 'default_social_image',

                label: 'Default social image configured',

                category: 'presentation',

                maxScore: 8,

                status: $this->filled(
                    $settings
                        ->defaultOgImage,
                )
                        ? 'pass'
                        : 'warn',

                detail: $this->filled(
                    $settings
                        ->defaultOgImage,
                )
                        ? 'A global fallback social image is configured.'
                        : 'No global fallback social image is configured.',

                recommendation: 'Configure a high-quality fallback image for entities without a preferred social image.',
            ),

            $this->check(
                id: 'organization_name',

                label: 'Organization name configured',

                category: 'structured_identity',

                maxScore: 5,

                status: $this->filled(
                    $settings
                        ->organizationName,
                )
                        ? 'pass'
                        : 'warn',

                detail: $this->filled(
                    $settings
                        ->organizationName,
                )
                        ? 'Organization identity has a configured name.'
                        : 'Organization identity has no configured name.',

                recommendation: 'Set the canonical organization/business name used by structured data.',
            ),

            $this->check(
                id: 'organization_logo',

                label: 'Organization logo configured',

                category: 'structured_identity',

                maxScore: 5,

                status: $this->filled(
                    $settings
                        ->organizationLogo,
                )
                        ? 'pass'
                        : 'warn',

                detail: $this->filled(
                    $settings
                        ->organizationLogo,
                )
                        ? 'Organization identity has a logo.'
                        : 'Organization identity has no configured logo.',

                recommendation: 'Configure an organization logo suitable for structured data and brand identity.',
            ),

            $this->check(
                id: 'default_meta_description',

                label: 'Default meta description configured',

                category: 'presentation',

                maxScore: 5,

                status: $this->filled(
                    $settings
                        ->defaultMetaDescription,
                )
                        ? 'pass'
                        : 'warn',

                detail: $this->filled(
                    $settings
                        ->defaultMetaDescription,
                )
                        ? 'A fallback description is available.'
                        : 'No global fallback meta description is configured.',

                recommendation: 'Provide a factual global fallback description for entities without a more specific description.',
            ),

            $this->check(
                id: 'search_verification',

                label: 'Search-engine verification configured',

                category: 'structured_identity',

                maxScore: 5,

                status: $this->filled(
                    $settings
                        ->googleSiteVerification,
                )
                    || $this->filled(
                        $settings
                            ->bingSiteVerification,
                    )
                        ? 'pass'
                        : 'warn',

                detail: $this->filled(
                    $settings
                        ->googleSiteVerification,
                )
                    || $this->filled(
                        $settings
                            ->bingSiteVerification,
                    )
                        ? 'At least one search-engine verification token is configured.'
                        : 'No Google or Bing verification token is configured.',

                recommendation: 'Add at least one webmaster/search-engine verification token when available.',
            ),

            $this->progressCheck(
                id: 'analysis_coverage',

                label: 'SEO analysis coverage',

                category: 'operations',

                maxScore: 7,

                percent: $coverage[
                        'percent'
                    ],

                passAt: 90,

                warnAt: 50,

                detail: sprintf(
                    '%d of %d SEO entities have at least one deterministic analysis.',
                    $coverage[
                        'analyzed'
                    ],
                    $coverage[
                        'total'
                    ],
                ),

                recommendation: 'Run deterministic analysis across products, categories, brands and pages that are still untracked.',
            ),

            $this->progressCheck(
                id: 'analysis_freshness',

                label: 'Analysis freshness',

                category: 'operations',

                maxScore: 3,

                percent: $freshness[
                        'percent'
                    ],

                passAt: 90,

                warnAt: 70,

                detail: sprintf(
                    '%d of %d latest analyses were created within the last %d days.',
                    $freshness[
                        'fresh'
                    ],
                    $freshness[
                        'total'
                    ],
                    self::ANALYSIS_FRESH_DAYS,
                ),

                recommendation: 'Re-run analysis for entities whose latest score is older than the freshness window.',
            ),
        ];

        $healthScore =
            array_sum(
                array_column(
                    $checks,
                    'score',
                ),
            );

        $statusCounts = [
            'pass' => 0,
            'warn' => 0,
            'fail' => 0,
        ];

        foreach (
            $checks as $check
        ) {
            $status =
                $check[
                    'status'
                ];

            if (
                array_key_exists(
                    $status,
                    $statusCounts,
                )
            ) {
                $statusCounts[
                    $status
                ]++;
            }
        }

        return [
            'summary' => [
                'health_score' => $healthScore,

                'health_grade' => $this->grade(
                    $healthScore,
                ),

                'passing_checks' => $statusCounts[
                        'pass'
                    ],

                'warning_checks' => $statusCounts[
                        'warn'
                    ],

                'failed_checks' => $statusCounts[
                        'fail'
                    ],

                'analysis_coverage_percent' => $coverage[
                        'percent'
                    ],

                'analysis_freshness_percent' => $freshness[
                        'percent'
                    ],
            ],

            'checks' => $checks,

            'categories' => $this->categories(
                $checks,
            ),

            'coverage' => $coverage,

            'operations' => $this->operations(
                $latestAnalyses,
            ),

            'settings_snapshot' => [
            'canonical_host' => $settings
                ->canonicalHost,

            'default_robots_index' => $settings
                ->defaultRobotsIndex,

            'default_robots_follow' => $settings
                ->defaultRobotsFollow,

            'enable_sitemap' => $settings
                ->enableSitemap,

            'enable_structured_data' => $settings
                ->enableStructuredData,

            'enable_open_graph' => $settings
                ->enableOpenGraph,

            'enable_twitter_cards' => $settings
                ->enableTwitterCards,

            'twitter_card' => $settings
                ->twitterCard,
            ],
        ];
    }

    /**
     * @return Collection<int, AiSeoAnalysis>
     */
    private function latestAnalyses(): Collection
    {
        $ids =
            AiSeoAnalysis::query()
                ->selectRaw(
                    'MAX(id) as id',
                )
                ->groupBy(
                    'entity_type',
                    'entity_id',
                )
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $id,
                    ): int => (int) $id,
                )
                ->all();

        if ($ids === []) {
            return new Collection;
        }

        return AiSeoAnalysis::query()
            ->whereIn(
                'id',
                $ids,
            )
            ->get();
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return array{
     *     total: int,
     *     analyzed: int,
     *     percent: int,
     *     by_type: list<array{
     *         entity_type: string,
     *         label: string,
     *         total: int,
     *         analyzed: int,
     *         percent: int
     *     }>
     * }
     */
    private function coverage(
        Collection $latestAnalyses,
    ): array {
        $inventory = [
            'product' => [
                'label' => 'Products',
                'total' => Product::query()
                    ->count(),
            ],
            'category' => [
                'label' => 'Categories',
                'total' => Category::query()
                    ->count(),
            ],
            'brand' => [
                'label' => 'Brands',
                'total' => Brand::query()
                    ->count(),
            ],
            'page' => [
                'label' => 'Pages',
                'total' => Page::query()
                    ->count(),
            ],
        ];

        $analyzedByType = [];

        foreach (
            $latestAnalyses as $analysis
        ) {
            $type =
                $analysis
                    ->entity_type
                    ->value;

            $analyzedByType[
                $type
            ] =
                ($analyzedByType[
                    $type
                ] ?? 0)
                + 1;
        }

        $rows = [];
        $total = 0;
        $analyzed = 0;

        foreach (
            $inventory as $type => $item
        ) {
            $typeTotal =
                $item[
                    'total'
                ];

            $typeAnalyzed =
                (int) (
                    $analyzedByType[
                        $type
                    ]
                    ?? 0
                );

            $total +=
                $typeTotal;

            $analyzed +=
                $typeAnalyzed;

            $rows[] = [
                'entity_type' => $type,

                'label' => $item[
                        'label'
                    ],

                'total' => $typeTotal,

                'analyzed' => $typeAnalyzed,

                'percent' => $typeTotal === 0
                        ? 0
                        : (int) round(
                            ($typeAnalyzed / $typeTotal)
                            * 100,
                        ),
            ];
        }

        return [
            'total' => $total,

            'analyzed' => $analyzed,

            'percent' => $total === 0
                    ? 0
                    : (int) round(
                        ($analyzed / $total)
                        * 100,
                    ),

            'by_type' => $rows,
        ];
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return array{
     *     total: int,
     *     fresh: int,
     *     old: int,
     *     percent: int,
     *     fresh_days: int
     * }
     */
    private function analysisFreshness(
        Collection $latestAnalyses,
    ): array {
        $cutoff =
            now()
                ->subDays(
                    self::ANALYSIS_FRESH_DAYS,
                );

        $fresh = 0;

        foreach (
            $latestAnalyses as $analysis
        ) {
            if (
                $analysis
                    ->analyzed_at
                    ->greaterThanOrEqualTo(
                        $cutoff,
                    )
            ) {
                $fresh++;
            }
        }

        $total =
            $latestAnalyses
                ->count();

        return [
            'total' => $total,

            'fresh' => $fresh,

            'old' => $total
                - $fresh,

            'percent' => $total === 0
                    ? 0
                    : (int) round(
                        ($fresh / $total)
                        * 100,
                    ),

            'fresh_days' => self::ANALYSIS_FRESH_DAYS,
        ];
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return array<string, mixed>
     */
    private function operations(
        Collection $latestAnalyses,
    ): array {
        $noindex = 0;
        $criticalFindings = 0;
        $warningFindings = 0;

        foreach (
            $latestAnalyses as $analysis
        ) {
            $robotsIndex =
                $analysis
                    ->resolved_seo[
                        'robots_index'
                    ]
                ?? null;

            if (
                $robotsIndex ===
                false
            ) {
                $noindex++;
            }

            foreach (
                $analysis
                    ->findings as $finding
            ) {
                $severity =
                    (string) (
                        $finding[
                            'severity'
                        ]
                        ?? ''
                    );

                if (
                    $severity ===
                    'critical'
                ) {
                    $criticalFindings++;
                } elseif (
                    $severity ===
                    'warning'
                ) {
                    $warningFindings++;
                }
            }
        }

        $since =
            now()
                ->subDays(
                    30,
                );

        $failedRequests =
            AiSeoRequest::query()
                ->where(
                    'created_at',
                    '>=',
                    $since,
                )
                ->where(
                    'status',
                    AiSeoRequestStatus::Failed
                        ->value,
                )
                ->count();

        $failedEnrichments =
            AiSeoAnalysisEnrichment::query()
                ->where(
                    'created_at',
                    '>=',
                    $since,
                )
                ->where(
                    'status',
                    'failed',
                )
                ->count();

        $pendingSuggestions =
            AiSeoSuggestion::query()
                ->whereHas(
                    'request',
                    static function (
                        Builder $query,
                    ): void {
                        $query->where(
                            'status',
                            AiSeoRequestStatus::Completed
                                ->value,
                        );
                    },
                )
                ->whereDoesntHave(
                    'latestReview',
                )
                ->count();

        $requestStatuses =
            $this
                ->requestStatuses(
                    $since,
                );

        $enrichmentStatuses =
            $this
                ->enrichmentStatuses(
                    $since,
                );

        return [
            'latest_analysis_count' => $latestAnalyses
                ->count(),

            'latest_noindex_count' => $noindex,

            'critical_findings' => $criticalFindings,

            'warning_findings' => $warningFindings,

            'failed_requests_30d' => $failedRequests,

            'failed_enrichments_30d' => $failedEnrichments,

            'pending_suggestions' => $pendingSuggestions,

            'request_statuses_30d' => $requestStatuses,

            'enrichment_statuses_30d' => $enrichmentStatuses,
        ];
    }

    /**
     * @return list<array{status: string, label: string, count: int}>
     */
    private function requestStatuses(
        Carbon $since,
    ): array {
        $statuses = [
            'pending',
            'processing',
            'completed',
            'failed',
            'cancelled',
        ];

        $counts =
            AiSeoRequest::query()
                ->where(
                    'created_at',
                    '>=',
                    $since,
                )
                ->selectRaw(
                    'status, COUNT(*) as aggregate',
                )
                ->groupBy(
                    'status',
                )
                ->pluck(
                    'aggregate',
                    'status',
                );

        $rows = [];

        foreach (
            $statuses as $status
        ) {
            $rows[] = [
                'status' => $status,

                'label' => ucfirst(
                    $status,
                ),

                'count' => (int) (
                    $counts[
                        $status
                    ]
                    ?? 0
                ),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{status: string, label: string, count: int}>
     */
    private function enrichmentStatuses(
        Carbon $since,
    ): array {
        $statuses = [
            'pending',
            'completed',
            'failed',
        ];

        $counts =
            AiSeoAnalysisEnrichment::query()
                ->where(
                    'created_at',
                    '>=',
                    $since,
                )
                ->selectRaw(
                    'status, COUNT(*) as aggregate',
                )
                ->groupBy(
                    'status',
                )
                ->pluck(
                    'aggregate',
                    'status',
                );

        $rows = [];

        foreach (
            $statuses as $status
        ) {
            $rows[] = [
                'status' => $status,

                'label' => ucfirst(
                    $status,
                ),

                'count' => (int) (
                    $counts[
                        $status
                    ]
                    ?? 0
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{
     *     id: string,
     *     label: string,
     *     category: string,
     *     status: string,
     *     score: int,
     *     max_score: int,
     *     detail: string,
     *     recommendation: string
     * }>  $checks
     * @return list<array{
     *     category: string,
     *     label: string,
     *     score: int,
     *     max_score: int,
     *     percent: int
     * }>
     */
    private function categories(
        array $checks,
    ): array {
        $labels = [
            'discovery' => 'Discovery & indexability',

            'presentation' => 'Social & metadata presentation',

            'structured_identity' => 'Structured data & identity',

            'operations' => 'Operational coverage',
        ];

        $rows = [];

        foreach (
            $labels as $category => $label
        ) {
            $score = 0;
            $maxScore = 0;

            foreach (
                $checks as $check
            ) {
                if (
                    $check[
                        'category'
                    ] !==
                    $category
                ) {
                    continue;
                }

                $score +=
                    $check[
                        'score'
                    ];

                $maxScore +=
                    $check[
                        'max_score'
                    ];
            }

            $rows[] = [
                'category' => $category,

                'label' => $label,

                'score' => $score,

                'max_score' => $maxScore,

                'percent' => $maxScore === 0
                        ? 0
                        : (int) round(
                            ($score / $maxScore)
                            * 100,
                        ),
            ];
        }

        return $rows;
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     category: string,
     *     status: string,
     *     score: int,
     *     max_score: int,
     *     detail: string,
     *     recommendation: string
     * }
     */
    private function check(
        string $id,
        string $label,
        string $category,
        int $maxScore,
        string $status,
        string $detail,
        string $recommendation,
    ): array {
        $score =
            match ($status) {
                'pass' => $maxScore,

                'warn' => (int) floor(
                    $maxScore
                    / 2,
                ),

                default => 0,
            };

        return [
            'id' => $id,

            'label' => $label,

            'category' => $category,

            'status' => $status,

            'score' => $score,

            'max_score' => $maxScore,

            'detail' => $detail,

            'recommendation' => $recommendation,
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     category: string,
     *     status: string,
     *     score: int,
     *     max_score: int,
     *     detail: string,
     *     recommendation: string
     * }
     */
    private function progressCheck(
        string $id,
        string $label,
        string $category,
        int $maxScore,
        int $percent,
        int $passAt,
        int $warnAt,
        string $detail,
        string $recommendation,
    ): array {
        $status =
            $percent >=
                $passAt
                ? 'pass'
                : (
                    $percent >=
                        $warnAt
                        ? 'warn'
                        : 'fail'
                );

        return [
            'id' => $id,

            'label' => $label,

            'category' => $category,

            'status' => $status,

            'score' => (int) round(
                $maxScore
                * (
                    max(
                        0,
                        min(
                            100,
                            $percent,
                        ),
                    )
                    / 100
                ),
            ),

            'max_score' => $maxScore,

            'detail' => $detail,

            'recommendation' => $recommendation,
        ];
    }

    private function httpsStatus(
        ?string $url,
    ): string {
        if (
            ! $this->filled(
                $url,
            )
        ) {
            return 'fail';
        }

        return str_starts_with(
            strtolower(
                trim(
                    (string) $url,
                ),
            ),
            'https://',
        )
            ? 'pass'
            : 'warn';
    }

    private function filled(
        ?string $value,
    ): bool {
        return $value !== null
            && trim(
                $value,
            ) !== '';
    }

    private function grade(
        int $score,
    ): string {
        return match (true) {
            $score >= 90 => 'excellent',

            $score >= 75 => 'good',

            $score >= 50 => 'needs_attention',

            default => 'critical',
        };
    }
}
