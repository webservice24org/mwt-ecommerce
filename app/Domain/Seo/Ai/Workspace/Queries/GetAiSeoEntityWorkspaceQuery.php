<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Queries;

use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoAnalysis;
use App\Models\AiSeoAnalysisEnrichment;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JsonException;

final readonly class GetAiSeoEntityWorkspaceQuery
{
    public function __construct(
        private AiSeoEntitySnapshotBuilder $snapshots,
        private AiSeoSnapshotHasher $hasher,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function handle(
        AiSeoEntityType $entityType,
        int $entityId,
    ): array {
        $currentSnapshot =
            $this
                ->snapshots
                ->build(
                    $entityType,
                    $entityId,
                )
                ->toArray();

        $currentSnapshotHash =
            $this
                ->hasher
                ->hash(
                    $currentSnapshot,
                );

        $analyses =
            AiSeoAnalysis::query()
                ->where(
                    'entity_type',
                    $entityType->value,
                )
                ->where(
                    'entity_id',
                    $entityId,
                )
                ->orderByDesc(
                    'analyzed_at',
                )
                ->orderByDesc(
                    'id',
                )
                ->limit(
                    20,
                )
                ->get();

        $latestAnalysis =
            $analyses->first();

        $latestEnrichment =
            $latestAnalysis === null
                ? null
                : AiSeoAnalysisEnrichment::query()
                    ->where(
                        'ai_seo_analysis_id',
                        $latestAnalysis->id,
                    )
                    ->orderByDesc(
                        'id',
                    )
                    ->first();

        $suggestions =
            AiSeoSuggestion::query()
                ->with([
                    'request',
                    'latestReview',
                ])
                ->whereHas(
                    'request',
                    static function (
                        Builder $query,
                    ) use (
                        $entityType,
                        $entityId,
                    ): void {
                        $query
                            ->where(
                                'entity_type',
                                $entityType->value,
                            )
                            ->where(
                                'entity_id',
                                $entityId,
                            )
                            ->where(
                                'status',
                                AiSeoRequestStatus::Completed
                                    ->value,
                            );
                    },
                )
                ->orderByDesc(
                    'ai_seo_request_id',
                )
                ->orderBy(
                    'position',
                )
                ->orderBy(
                    'id',
                )
                ->limit(
                    50,
                )
                ->get();

        $requests =
            AiSeoRequest::query()
                ->where(
                    'entity_type',
                    $entityType->value,
                )
                ->where(
                    'entity_id',
                    $entityId,
                )
                ->orderByDesc(
                    'id',
                )
                ->limit(
                    12,
                )
                ->get();

        return [
            'entity' => $this->entity(
                $currentSnapshot,
            ),

            'summary' => $this->summary(
                latestAnalysis: $latestAnalysis,

                latestEnrichment: $latestEnrichment,

                suggestions: $suggestions,

                currentSnapshotHash: $currentSnapshotHash,
            ),

            'latest_analysis' => $latestAnalysis === null
                    ? null
                    : $this->analysis(
                        $latestAnalysis,
                        $currentSnapshotHash,
                    ),

            'latest_enrichment' => $latestEnrichment === null
                    ? null
                    : $this->enrichment(
                        $latestEnrichment,
                    ),

            'analysis_history' => $analyses
                ->map(
                    static fn (
                        AiSeoAnalysis $analysis,
                    ): array => [
                        'id' => $analysis->id,

                        'score' => $analysis->score,

                        'grade' => $analysis
                            ->grade
                            ->value,

                        'finding_count' => count(
                            $analysis
                                ->findings,
                        ),

                        'analyzed_at' => $analysis
                            ->analyzed_at
                            ->toIso8601String(),
                    ],
                )
                ->values()
                ->all(),

            'suggestions' => $this->suggestions(
                $suggestions,
                $currentSnapshotHash,
            ),

            'requests' => $requests
                ->map(
                    static fn (
                        AiSeoRequest $request,
                    ): array => [
                        'id' => $request->id,

                        'status' => $request
                            ->status
                            ->value,

                        'provider' => $request
                            ->provider,

                        'model_name' => $request
                            ->model_name,

                        'prompt_version' => $request
                            ->prompt_version,

                        'error_code' => $request
                            ->error_code,

                        'error_message' => $request
                            ->error_message,

                        'started_at' => $request
                            ->started_at
                            ?->toIso8601String(),

                        'completed_at' => $request
                            ->completed_at
                            ?->toIso8601String(),

                        'cancelled_at' => $request
                            ->cancelled_at
                            ?->toIso8601String(),
                    ],
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{
     *     type: string,
     *     id: int,
     *     name: string,
     *     slug: string,
     *     current_seo: array<string, string|null>,
     *     content: array<string, mixed>,
     *     context: array<string, mixed>
     * }
     */
    private function entity(
        array $snapshot,
    ): array {
        $entity =
            $snapshot[
                'entity'
            ];

        $currentSeo =
            $snapshot[
                'current_seo'
            ];

        $content =
            $snapshot[
                'content'
            ];

        $context =
            $snapshot[
                'context'
            ];

        /** @var array{
         *     type: string,
         *     id: int,
         *     name: string,
         *     slug: string
         * } $entity
         * @var array<string, string|null> $currentSeo
         * @var array<string, mixed> $content
         * @var array<string, mixed> $context
         */
        return [
            'type' => $entity[
                    'type'
                ],

            'id' => $entity[
                    'id'
                ],

            'name' => $entity[
                    'name'
                ],

            'slug' => $entity[
                    'slug'
                ],

            'current_seo' => $currentSeo,

            'content' => $content,

            'context' => $context,
        ];
    }

    /**
     * @param  Collection<int, AiSeoSuggestion>  $suggestions
     * @return array{
     *     latest_score: int|null,
     *     latest_grade: string|null,
     *     analysis_stale: bool,
     *     finding_count: int,
     *     suggestion_count: int,
     *     pending_suggestions: int,
     *     approved_suggestions: int,
     *     rejected_suggestions: int,
     *     stale_suggestions: int,
     *     latest_analysis_at: string|null,
     *     latest_enrichment_status: string|null
     * }
     *
     * @throws JsonException
     */
    private function summary(
        ?AiSeoAnalysis $latestAnalysis,
        ?AiSeoAnalysisEnrichment $latestEnrichment,
        Collection $suggestions,
        string $currentSnapshotHash,
    ): array {
        $pending = 0;
        $approved = 0;
        $rejected = 0;
        $stale = 0;

        $freshnessByRequest = [];

        foreach (
            $suggestions as $suggestion
        ) {
            $review =
                $suggestion
                    ->latestReview;

            if ($review === null) {
                $pending++;
            } elseif (
                $review
                    ->decision
                    ->value
                === 'approved'
            ) {
                $approved++;
            } else {
                $rejected++;
            }

            $request =
                $suggestion
                    ->request;

            if (
                ! array_key_exists(
                    $request->id,
                    $freshnessByRequest,
                )
            ) {
                $freshnessByRequest[
                    $request->id
                ] =
                    $this->requestIsStale(
                        $request,
                        $currentSnapshotHash,
                    );
            }

            if (
                $freshnessByRequest[
                    $request->id
                ]
            ) {
                $stale++;
            }
        }

        return [
            'latest_score' => $latestAnalysis
                ?->score,

            'latest_grade' => $latestAnalysis
                ?->grade
                ->value,

            'analysis_stale' => $latestAnalysis !== null
                && ! hash_equals(
                    $latestAnalysis
                        ->snapshot_hash,
                    $currentSnapshotHash,
                ),

            'finding_count' => $latestAnalysis === null
                    ? 0
                    : count(
                        $latestAnalysis
                            ->findings,
                    ),

            'suggestion_count' => $suggestions
                ->count(),

            'pending_suggestions' => $pending,

            'approved_suggestions' => $approved,

            'rejected_suggestions' => $rejected,

            'stale_suggestions' => $stale,

            'latest_analysis_at' => $latestAnalysis === null
                    ? null
                    : $latestAnalysis
                        ->analyzed_at
                        ->toIso8601String(),

            'latest_enrichment_status' => $latestEnrichment
                ?->status
                ->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analysis(
        AiSeoAnalysis $analysis,
        string $currentSnapshotHash,
    ): array {
        return [
            'id' => $analysis->id,

            'score' => $analysis->score,

            'grade' => $analysis
                ->grade
                ->value,

            'analysis_version' => $analysis
                ->analysis_version,

            'snapshot_schema_version' => $analysis
                ->snapshot_schema_version,

            'snapshot_hash' => $analysis
                ->snapshot_hash,

            'analysis_input_hash' => $analysis
                ->analysis_input_hash,

            'is_stale' => ! hash_equals(
                $analysis
                    ->snapshot_hash,
                $currentSnapshotHash,
            ),

            'breakdown' => $analysis
                ->breakdown,

            'rules' => $analysis
                ->rules,

            'findings' => $analysis
                ->findings,

            'resolved_seo' => $analysis
                ->resolved_seo,

            'analyzed_by_admin_id' => $analysis
                ->analyzed_by_admin_id,

            'analyzed_at' => $analysis
                ->analyzed_at
                ->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichment(
        AiSeoAnalysisEnrichment $enrichment,
    ): array {
        return [
            'id' => $enrichment->id,

            'analysis_id' => $enrichment
                ->ai_seo_analysis_id,

            'status' => $enrichment
                ->status
                ->value,

            'summary' => $enrichment
                ->summary,

            'actions' => $enrichment
                ->actions
                ?? [],

            'provider' => $enrichment
                ->provider,

            'model_name' => $enrichment
                ->model_name,

            'prompt_version' => $enrichment
                ->prompt_version,

            'error_code' => $enrichment
                ->error_code,

            'error_message' => $enrichment
                ->error_message,

            'started_at' => $enrichment
                ->started_at
                ->toIso8601String(),

            'completed_at' => $enrichment
                ->completed_at
                ?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, AiSeoSuggestion>  $suggestions
     * @return list<array<string, mixed>>
     *
     * @throws JsonException
     */
    private function suggestions(
        Collection $suggestions,
        string $currentSnapshotHash,
    ): array {
        $freshnessByRequest = [];

        return $suggestions
            ->map(
                function (
                    AiSeoSuggestion $suggestion,
                ) use (
                    $currentSnapshotHash,
                    &$freshnessByRequest,
                ): array {
                    $request =
                        $suggestion
                            ->request;

                    if (
                        ! array_key_exists(
                            $request->id,
                            $freshnessByRequest,
                        )
                    ) {
                        $freshnessByRequest[
                            $request->id
                        ] =
                            $this->requestFreshness(
                                $request,
                                $currentSnapshotHash,
                            );
                    }

                    /** @var array{
                     *     is_stale: bool,
                     *     state: string,
                     *     source_snapshot_hash: string|null,
                     *     current_snapshot_hash: string
                     * } $freshness
                     */
                    $freshness =
                        $freshnessByRequest[
                            $request->id
                        ];

                    $sourceSnapshot =
                        $request
                            ->input_snapshot[
                                'entity_snapshot'
                            ]
                        ?? [];

                    if (! is_array($sourceSnapshot)) {
                        $sourceSnapshot = [];
                    }

                    $sourceSeo =
                        $sourceSnapshot[
                            'current_seo'
                        ]
                        ?? [];

                    if (! is_array($sourceSeo)) {
                        $sourceSeo = [];
                    }

                    $existingValue =
                        $sourceSeo[
                            $suggestion
                                ->field
                                ->value
                        ]
                        ?? null;

                    $review =
                        $suggestion
                            ->latestReview;

                    return [
                        'id' => $suggestion->id,

                        'request_id' => $request->id,

                        'field' => $suggestion
                            ->field
                            ->value,

                        'field_label' => $this->fieldLabel(
                            $suggestion
                                ->field
                                ->value,
                        ),

                        'existing_value' => is_string(
                            $existingValue,
                        )
                                ? $existingValue
                                : null,

                        'suggested_value' => $suggestion
                            ->value,

                        'rationale' => $suggestion
                            ->rationale,

                        'review_status' => $review
                            ?->decision
                            ->value
                            ?? 'pending',

                        'reviewed_at' => $review
                            ?->reviewed_at
                            ->toIso8601String(),

                        'review_note' => $review
                            ?->note,

                        'freshness' => $freshness,

                        'can_approve' => ! $freshness[
                                'is_stale'
                            ],

                        'provider' => $request
                            ->provider,

                        'model_name' => $request
                            ->model_name,

                        'prompt_version' => $request
                            ->prompt_version,

                        'generated_at' => $request
                            ->completed_at
                            ?->toIso8601String(),
                    ];
                },
            )
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     is_stale: bool,
     *     state: string,
     *     source_snapshot_hash: string|null,
     *     current_snapshot_hash: string
     * }
     *
     * @throws JsonException
     */
    private function requestFreshness(
        AiSeoRequest $request,
        string $currentSnapshotHash,
    ): array {
        $sourceSnapshot =
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]
            ?? null;

        if (! is_array($sourceSnapshot)) {
            return [
                'is_stale' => true,

                'state' => 'source_snapshot_missing',

                'source_snapshot_hash' => null,

                'current_snapshot_hash' => $currentSnapshotHash,
            ];
        }

        $sourceSnapshotHash =
            $this
                ->hasher
                ->hash(
                    $sourceSnapshot,
                );

        $isStale =
            ! hash_equals(
                $sourceSnapshotHash,
                $currentSnapshotHash,
            );

        return [
            'is_stale' => $isStale,

            'state' => $isStale
                    ? 'stale'
                    : 'fresh',

            'source_snapshot_hash' => $sourceSnapshotHash,

            'current_snapshot_hash' => $currentSnapshotHash,
        ];
    }

    /**
     * @throws JsonException
     */
    private function requestIsStale(
        AiSeoRequest $request,
        string $currentSnapshotHash,
    ): bool {
        return $this
            ->requestFreshness(
                $request,
                $currentSnapshotHash,
            )['is_stale'];
    }

    private function fieldLabel(
        string $field,
    ): string {
        return match ($field) {
            'meta_title' => 'Meta title',

            'meta_description' => 'Meta description',

            'og_title' => 'Open Graph title',

            'og_description' => 'Open Graph description',

            'twitter_title' => 'X/Twitter title',

            'twitter_description' => 'X/Twitter description',

            default => str(
                $field,
            )
                ->replace(
                    '_',
                    ' ',
                )
                ->title()
                ->toString(),
        };
    }
}
