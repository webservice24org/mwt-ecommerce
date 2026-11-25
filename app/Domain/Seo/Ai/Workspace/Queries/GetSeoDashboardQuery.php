<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Queries;

use App\Models\AiSeoAnalysis;
use App\Models\AiSeoAnalysisEnrichment;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection;

final class GetSeoDashboardQuery
{
    /**
     * @return array<string, mixed>
     */
    public function handle(
        ?string $entityType = null,
    ): array {
        $entityType =
            $this->normalizeEntityType(
                $entityType,
            );

        $latestAnalyses =
            $this->latestAnalyses(
                $entityType,
            );

        $enrichmentsByAnalysis =
            $this->enrichmentsByAnalysis(
                $latestAnalyses,
            );

        $coverage =
            $this->coverage(
                $latestAnalyses,
                $entityType,
            );

        return [
            'summary' => $this->summary(
                $latestAnalyses,
                $enrichmentsByAnalysis,
                $coverage,
                $entityType,
            ),

            'coverage' => $coverage,

            'charts' => [
            'score_bands' => $this->scoreBands(
                $latestAnalyses,
            ),

            'grade_distribution' => $this->gradeDistribution(
                $latestAnalyses,
            ),

            'entity_type_averages' => $this->entityTypeAverages(
                $latestAnalyses,
            ),

            'finding_categories' => $this->findingCategories(
                $latestAnalyses,
            ),

            'enrichment_statuses' => $this->enrichmentStatuses(
                $latestAnalyses,
                $enrichmentsByAnalysis,
            ),
            ],

            'classic' => [
                'top_entities' => $this->topEntities(
                    $latestAnalyses,
                    $enrichmentsByAnalysis,
                ),

                'recent_entities' => $this->recentEntities(
                    $latestAnalyses,
                    $enrichmentsByAnalysis,
                ),

                'issue_hotspots' => $this->issueHotspots(
                    $latestAnalyses,
                ),
            ],

            'filters' => [
                'entity_type' => $entityType,

                'available_entity_types' => [
                [
                    'value' => '',

                    'label' => 'All entities',
                ],

                [
                    'value' => 'product',

                    'label' => 'Products',
                ],

                [
                    'value' => 'category',

                    'label' => 'Categories',
                ],

                [
                    'value' => 'brand',

                    'label' => 'Brands',
                ],

                [
                    'value' => 'page',

                    'label' => 'Pages',
                ],
                ],
            ],
        ];
    }

    /**
     * @return Collection<int, AiSeoAnalysis>
     */
    private function latestAnalyses(
        ?string $entityType,
    ): Collection {
        $idQuery =
            AiSeoAnalysis::query()
                ->selectRaw(
                    'MAX(id) as id',
                )
                ->groupBy(
                    'entity_type',
                    'entity_id',
                );

        if ($entityType !== null) {
            $idQuery->where(
                'entity_type',
                $entityType,
            );
        }

        $ids =
            $idQuery
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
            ->orderByDesc(
                'analyzed_at',
            )
            ->orderByDesc(
                'id',
            )
            ->get();
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return array<int, list<AiSeoAnalysisEnrichment>>
     */
    private function enrichmentsByAnalysis(
        Collection $latestAnalyses,
    ): array {
        $analysisIds =
            $latestAnalyses
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $id,
                    ): int => (int) $id,
                )
                ->all();

        if ($analysisIds === []) {
            return [];
        }

        $rows =
            AiSeoAnalysisEnrichment::query()
                ->whereIn(
                    'ai_seo_analysis_id',
                    $analysisIds,
                )
                ->orderByDesc(
                    'id',
                )
                ->get();

        $grouped = [];

        foreach (
            $rows as $enrichment
        ) {
            $analysisId =
                (int) $enrichment
                    ->ai_seo_analysis_id;

            $grouped[
                $analysisId
            ][] =
                $enrichment;
        }

        return $grouped;
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return array{
     *     products: array{label: string, total: int, analyzed: int, percent: int},
     *     categories: array{label: string, total: int, analyzed: int, percent: int},
     *     brands: array{label: string, total: int, analyzed: int, percent: int},
     *     pages: array{label: string, total: int, analyzed: int, percent: int}
     * }
     */
    private function coverage(
        Collection $latestAnalyses,
        ?string $entityType,
    ): array {
        $counts =
            $latestAnalyses
                ->groupBy(
                    static fn (
                        AiSeoAnalysis $analysis,
                    ): string => $analysis
                        ->entity_type
                        ->value,
                )
                ->map(
                    static fn (
                        Collection $items,
                    ): int => $items->count(),
                );

        $map = [
            'products' => [
                'type' => 'product',
                'label' => 'Products',
                'total' => $entityType !== null
                    && $entityType !== 'product'
                        ? 0
                        : Product::query()
                            ->count(),
            ],

            'categories' => [
                'type' => 'category',
                'label' => 'Categories',
                'total' => $entityType !== null
                    && $entityType !== 'category'
                        ? 0
                        : Category::query()
                            ->count(),
            ],

            'brands' => [
                'type' => 'brand',
                'label' => 'Brands',
                'total' => $entityType !== null
                    && $entityType !== 'brand'
                        ? 0
                        : Brand::query()
                            ->count(),
            ],

            'pages' => [
                'type' => 'page',
                'label' => 'Pages',
                'total' => $entityType !== null
                    && $entityType !== 'page'
                        ? 0
                        : Page::query()
                            ->count(),
            ],
        ];

        $result = [];

        foreach (
            $map as $key => $item
        ) {
            $analyzed =
                (int) (
                    $counts[
                        $item['type']
                    ]
                    ?? 0
                );

            $total =
                (int) $item[
                    'total'
                ];

            $result[
                $key
            ] = [
                'label' => $item['label'],
                'total' => $total,
                'analyzed' => $analyzed,
                'percent' => $total === 0
                        ? 0
                        : (int) round(
                            ($analyzed / $total)
                            * 100,
                        ),
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @param  array<int, list<AiSeoAnalysisEnrichment>>  $enrichmentsByAnalysis
     * @param  array<string, array{label: string, total: int, analyzed: int, percent: int}>  $coverage
     * @return array<string, mixed>
     */
    private function summary(
        Collection $latestAnalyses,
        array $enrichmentsByAnalysis,
        array $coverage,
        ?string $entityType,
    ): array {
        $scores =
            $latestAnalyses
                ->pluck(
                    'score',
                )
                ->map(
                    static fn (
                        mixed $score,
                    ): int => (int) $score,
                );

        $averageScore =
            $scores->count() === 0
                ? 0
                : (int) round(
                    $scores->avg()
                    ?? 0,
                );

        $highPriorityCount = 0;
        $warningCount = 0;
        $infoCount = 0;

        foreach (
            $latestAnalyses as $analysis
        ) {
            foreach (
                $analysis->findings as $finding
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
                    $highPriorityCount++;
                } elseif (
                    $severity ===
                    'warning'
                ) {
                    $warningCount++;
                } elseif (
                    $severity ===
                    'info'
                ) {
                    $infoCount++;
                }
            }
        }

        $completedEnrichments = 0;
        $failedEnrichments = 0;

        foreach (
            $enrichmentsByAnalysis as $history
        ) {
            foreach (
                $history as $enrichment
            ) {
                $status =
                    $enrichment
                        ->status
                        ->value;

                if ($status === 'completed') {
                    $completedEnrichments++;
                } elseif ($status === 'failed') {
                    $failedEnrichments++;
                }
            }
        }

        $coveragePercent =
            collect($coverage)
                ->filter(
                    static fn (
                        array $item,
                    ): bool => $item['total'] > 0,
                )
                ->avg(
                    'percent',
                );

        return [
            'entity_scope' => $entityType
                ?? 'all',

            'tracked_entities' => $latestAnalyses->count(),

            'average_score' => $averageScore,

            'coverage_percent' => (int) round(
                $coveragePercent
                ?? 0,
            ),

            'critical_findings' => $highPriorityCount,

            'warning_findings' => $warningCount,

            'info_findings' => $infoCount,

            'completed_enrichments' => $completedEnrichments,

            'failed_enrichments' => $failedEnrichments,

            'latest_analysis_at' => $latestAnalyses
                ->first()
                ?->analyzed_at
                ->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return list<array{label: string, min: int, max: int, count: int}>
     */
    private function scoreBands(
        Collection $latestAnalyses,
    ): array {
        $bands = [
            [
                'label' => '0–49',
                'min' => 0,
                'max' => 49,
                'count' => 0,
            ],
            [
                'label' => '50–69',
                'min' => 50,
                'max' => 69,
                'count' => 0,
            ],
            [
                'label' => '70–89',
                'min' => 70,
                'max' => 89,
                'count' => 0,
            ],
            [
                'label' => '90–100',
                'min' => 90,
                'max' => 100,
                'count' => 0,
            ],
        ];

        foreach (
            $latestAnalyses as $analysis
        ) {
            $score =
                (int) $analysis->score;

            foreach (
                $bands as $index => $band
            ) {
                if (
                    $score >=
                        $band['min']
                    && $score <=
                        $band['max']
                ) {
                    $bands[
                        $index
                    ]['count']++;

                    break;
                }
            }
        }

        return $bands;
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return list<array{grade: string, label: string, count: int}>
     */
    private function gradeDistribution(
        Collection $latestAnalyses,
    ): array {
        $labels = [
            'excellent' => 'Excellent',
            'good' => 'Good',
            'needs_work' => 'Needs work',
            'poor' => 'Poor',
        ];

        $counts =
            $latestAnalyses
                ->groupBy(
                    static fn (
                        AiSeoAnalysis $analysis,
                    ): string => $analysis
                        ->grade
                        ->value,
                )
                ->map(
                    static fn (
                        Collection $items,
                    ): int => $items->count(),
                );

        $result = [];

        foreach (
            $labels as $grade => $label
        ) {
            $result[] = [
                'grade' => $grade,
                'label' => $label,
                'count' => (int) (
                    $counts[
                        $grade
                    ]
                    ?? 0
                ),
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return list<array{entity_type: string, label: string, average_score: int, count: int}>
     */
    private function entityTypeAverages(
        Collection $latestAnalyses,
    ): array {
        $labels = [
            'product' => 'Products',
            'category' => 'Categories',
            'brand' => 'Brands',
            'page' => 'Pages',
        ];

        $grouped =
            $latestAnalyses
                ->groupBy(
                    static fn (
                        AiSeoAnalysis $analysis,
                    ): string => $analysis
                        ->entity_type
                        ->value,
                );

        $rows = [];

        foreach (
            $labels as $type => $label
        ) {
            /** @var Collection<int, AiSeoAnalysis> $items */
            $items =
                $grouped->get(
                    $type,
                    new Collection,
                );

            $rows[] = [
                'entity_type' => $type,
                'label' => $label,
                'average_score' => $items->count() === 0
                        ? 0
                        : (int) round(
                            $items->avg(
                                'score',
                            )
                            ?? 0,
                        ),
                'count' => $items->count(),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return list<array{category: string, label: string, count: int}>
     */
    private function findingCategories(
        Collection $latestAnalyses,
    ): array {
        $counts = [];

        foreach (
            $latestAnalyses as $analysis
        ) {
            foreach (
                $analysis->findings as $finding
            ) {
                $category =
                    trim(
                        (string) (
                            $finding[
                                'category'
                            ]
                            ?? ''
                        ),
                    );

                if ($category === '') {
                    continue;
                }

                $counts[
                    $category
                ] =
                    ($counts[
                        $category
                    ] ?? 0)
                    + 1;
            }
        }

        arsort(
            $counts,
        );

        $rows = [];

        foreach (
            array_slice(
                $counts,
                0,
                6,
                true,
            ) as $category => $count
        ) {
            $rows[] = [
                'category' => $category,
                'label' => str(
                    $category,
                )
                    ->replace(
                        '_',
                        ' ',
                    )
                    ->title()
                    ->toString(),
                'count' => $count,
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @param  array<int, list<AiSeoAnalysisEnrichment>>  $enrichmentsByAnalysis
     * @return list<array{status: string, label: string, count: int}>
     */
    private function enrichmentStatuses(
        Collection $latestAnalyses,
        array $enrichmentsByAnalysis,
    ): array {
        $counts = [
            'completed' => 0,
            'failed' => 0,
            'pending' => 0,
            'none' => 0,
        ];

        foreach (
            $latestAnalyses as $analysis
        ) {
            $history =
                $enrichmentsByAnalysis[
                    $analysis->id
                ]
                ?? [];

            if ($history === []) {
                $counts[
                    'none'
                ]++;

                continue;
            }

            $latest =
                $history[0];

            $status =
                $latest
                    ->status
                    ->value;

            if (
                array_key_exists(
                    $status,
                    $counts,
                )
            ) {
                $counts[
                    $status
                ]++;
            }
        }

        return [
            [
                'status' => 'completed',
                'label' => 'Completed',
                'count' => $counts[
                        'completed'
                    ],
            ],
            [
                'status' => 'failed',
                'label' => 'Failed',
                'count' => $counts[
                        'failed'
                    ],
            ],
            [
                'status' => 'pending',
                'label' => 'Pending',
                'count' => $counts[
                        'pending'
                    ],
            ],
            [
                'status' => 'none',
                'label' => 'No enrichment',
                'count' => $counts[
                        'none'
                    ],
            ],
        ];
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @param  array<int, list<AiSeoAnalysisEnrichment>>  $enrichmentsByAnalysis
     * @return list<array<string, mixed>>
     */
    private function topEntities(
        Collection $latestAnalyses,
        array $enrichmentsByAnalysis,
    ): array {
        return $latestAnalyses
            ->sortByDesc(
                'score',
            )
            ->take(10)
            ->values()
            ->map(
                fn (
                    AiSeoAnalysis $analysis,
                ): array => $this->analysisRow(
                    $analysis,
                    $enrichmentsByAnalysis,
                ),
            )
            ->all();
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @param  array<int, list<AiSeoAnalysisEnrichment>>  $enrichmentsByAnalysis
     * @return list<array<string, mixed>>
     */
    private function recentEntities(
        Collection $latestAnalyses,
        array $enrichmentsByAnalysis,
    ): array {
        return $latestAnalyses
            ->take(10)
            ->values()
            ->map(
                fn (
                    AiSeoAnalysis $analysis,
                ): array => $this->analysisRow(
                    $analysis,
                    $enrichmentsByAnalysis,
                ),
            )
            ->all();
    }

    /**
     * @param  array<int, list<AiSeoAnalysisEnrichment>>  $enrichmentsByAnalysis
     * @return array<string, mixed>
     */
    private function analysisRow(
        AiSeoAnalysis $analysis,
        array $enrichmentsByAnalysis,
    ): array {
        $history =
            $enrichmentsByAnalysis[
                $analysis->id
            ]
            ?? [];

        $latestEnrichment =
            $history[0]
            ?? null;

        return [
            'analysis_id' => $analysis->id,
            'entity_type' => $analysis
                ->entity_type
                ->value,
            'entity_type_label' => $this->entityTypeLabel(
                $analysis
                    ->entity_type
                    ->value,
            ),
            'entity_id' => $analysis
                ->entity_id,
            'score' => (int) $analysis->score,
            'grade' => $analysis
                ->grade
                ->value,
            'finding_count' => count(
                $analysis->findings,
            ),
            'analyzed_at' => $analysis
                ->analyzed_at
                ->toIso8601String(),
            'has_enrichment' => $latestEnrichment !== null,
            'enrichment_status' => $latestEnrichment
                ?->status
                ->value,
        ];
    }

    /**
     * @param  Collection<int, AiSeoAnalysis>  $latestAnalyses
     * @return list<array{finding_id: string, title: string, category: string, severity: string, count: int}>
     */
    private function issueHotspots(
        Collection $latestAnalyses,
    ): array {
        $hotspots = [];

        foreach (
            $latestAnalyses as $analysis
        ) {
            foreach (
                $analysis->findings as $finding
            ) {
                $findingId =
                    trim(
                        (string) (
                            $finding[
                                'id'
                            ]
                            ?? ''
                        ),
                    );

                if ($findingId === '') {
                    continue;
                }

                if (
                    ! isset(
                        $hotspots[
                            $findingId
                        ],
                    )
                ) {
                    $hotspots[
                        $findingId
                    ] = [
                        'finding_id' => $findingId,
                        'title' => (string) (
                            $finding[
                                'title'
                            ]
                            ?? $findingId
                        ),
                        'category' => (string) (
                            $finding[
                                'category'
                            ]
                            ?? 'unknown'
                        ),
                        'severity' => (string) (
                            $finding[
                                'severity'
                            ]
                            ?? 'info'
                        ),
                        'count' => 0,
                    ];
                }

                $hotspots[
                    $findingId
                ]['count']++;
            }
        }

        $rows =
            array_values(
                $hotspots,
            );

        usort(
            $rows,
            function (
                array $left,
                array $right,
            ): int {
                $severityOrder = [
                    'critical' => 3,
                    'warning' => 2,
                    'info' => 1,
                ];

                $leftWeight =
                    $severityOrder[
                        $left[
                            'severity'
                        ]
                    ]
                    ?? 0;

                $rightWeight =
                    $severityOrder[
                        $right[
                            'severity'
                        ]
                    ]
                    ?? 0;

                if (
                    $leftWeight !==
                    $rightWeight
                ) {
                    return $rightWeight
                        <=> $leftWeight;
                }

                if (
                    $left['count'] !==
                    $right['count']
                ) {
                    return $right['count']
                        <=> $left['count'];
                }

                /*
                 * Stable tie-breaker so dashboard ordering
                 * remains deterministic when severity and
                 * frequency are identical.
                 */
                return strcmp(
                    $left['finding_id'],
                    $right['finding_id'],
                );
            },
        );

        return array_slice(
            $rows,
            0,
            10,
        );
    }

    private function normalizeEntityType(
        ?string $entityType,
    ): ?string {
        $entityType =
            trim(
                strtolower(
                    (string) $entityType,
                ),
            );

        if ($entityType === '') {
            return null;
        }

        return in_array(
            $entityType,
            [
                'product',
                'category',
                'brand',
                'page',
            ],
            true,
        )
            ? $entityType
            : null;
    }

    private function entityTypeLabel(
        string $entityType,
    ): string {
        return match ($entityType) {
            'product' => 'Product',
            'category' => 'Category',
            'brand' => 'Brand',
            'page' => 'Page',
            default => 'Entity',
        };
    }
}
