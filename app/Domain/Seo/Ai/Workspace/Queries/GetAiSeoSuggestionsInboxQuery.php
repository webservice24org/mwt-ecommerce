<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Queries;

use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Workspace\Data\AiSeoSuggestionFreshnessData;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Domain\Seo\Ai\Workspace\Services\AiSeoSuggestionFreshnessChecker;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use Illuminate\Database\Eloquent\Builder;
use JsonException;

final readonly class GetAiSeoSuggestionsInboxQuery
{
    public function __construct(
        private AiSeoSuggestionFreshnessChecker $freshness,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function handle(
        ?string $search = null,
        ?string $entityType = null,
        ?string $field = null,
        ?string $provider = null,
        ?string $reviewStatus = null,
        int $page = 1,
    ): array {
        $search =
            $this->nullableString(
                $search,
            );

        $entityType =
            $this->allowedValue(
                $entityType,
                array_map(
                    static fn (
                        AiSeoEntityType $type,
                    ): string => $type->value,
                    AiSeoEntityType::cases(),
                ),
            );

        $field =
            $this->allowedValue(
                $field,
                array_map(
                    static fn (
                        AiSeoSuggestionField $item,
                    ): string => $item->value,
                    AiSeoSuggestionField::cases(),
                ),
            );

        $provider =
            $this->nullableString(
                $provider,
            );

        $reviewStatus =
            $this->allowedValue(
                $reviewStatus,
                [
                    'pending',
                    'approved',
                    'rejected',
                ],
            );

        $query =
            AiSeoSuggestion::query()
                ->with([
                    'request',
                    'latestReview',
                ])
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
                );

        if ($search !== null) {
            $like =
                '%'.$search.'%';

            $query->where(
                function (
                    Builder $query,
                ) use (
                    $like,
                    $search,
                ): void {
                    $query
                        ->where(
                            'value',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'rationale',
                            'like',
                            $like,
                        )
                        ->orWhereHas(
                            'request',
                            function (
                                Builder $requestQuery,
                            ) use (
                                $like,
                                $search,
                            ): void {
                                $requestQuery
                                    ->where(
                                        'input_snapshot->entity_snapshot->entity->name',
                                        'like',
                                        $like,
                                    )
                                    ->orWhere(
                                        'input_snapshot->entity_snapshot->entity->slug',
                                        'like',
                                        $like,
                                    );

                                if (
                                    ctype_digit(
                                        $search,
                                    )
                                ) {
                                    $requestQuery
                                        ->orWhere(
                                            'entity_id',
                                            (int) $search,
                                        );
                                }
                            },
                        );
                },
            );
        }

        if ($entityType !== null) {
            $query->whereHas(
                'request',
                static function (
                    Builder $requestQuery,
                ) use (
                    $entityType,
                ): void {
                    $requestQuery->where(
                        'entity_type',
                        $entityType,
                    );
                },
            );
        }

        if ($field !== null) {
            $query->where(
                'field',
                $field,
            );
        }

        if ($provider !== null) {
            $query->whereHas(
                'request',
                static function (
                    Builder $requestQuery,
                ) use (
                    $provider,
                ): void {
                    $requestQuery->where(
                        'provider',
                        $provider,
                    );
                },
            );
        }

        if ($reviewStatus === 'pending') {
            $query->whereDoesntHave(
                'latestReview',
            );
        } elseif (
            in_array(
                $reviewStatus,
                [
                    'approved',
                    'rejected',
                ],
                true,
            )
        ) {
            $query->whereHas(
                'latestReview',
                static function (
                    Builder $reviewQuery,
                ) use (
                    $reviewStatus,
                ): void {
                    $reviewQuery->where(
                        'decision',
                        $reviewStatus,
                    );
                },
            );
        }

        $paginator =
            $query
                ->orderByDesc(
                    'created_at',
                )
                ->orderByDesc(
                    'id',
                )
                ->paginate(
                    20,
                    [
                        '*',
                    ],
                    'page',
                    max(
                        1,
                        $page,
                    ),
                );

        $items = [];
        $freshnessByRequest = [];
        $staleOnPage = 0;

        foreach (
            $paginator->items() as $item
        ) {
            $request =
                $item
                    ->request;

            if (
                ! isset(
                    $freshnessByRequest[
                        $request->id
                    ],
                )
            ) {
                $freshnessByRequest[
                    $request->id
                ] =
                    $this
                        ->freshness
                        ->check(
                            $request,
                        );
            }

            /** @var AiSeoSuggestionFreshnessData $freshness */
            $freshness =
                $freshnessByRequest[
                    $request->id
                ];

            if (
                $freshness
                    ->isStale
            ) {
                $staleOnPage++;
            }

            $items[] =
                $this->item(
                    $item,
                    $request,
                    $freshness,
                );
        }

        return [
            'summary' => $this->summary(
                $staleOnPage,
            ),

            'items' => $items,

            'pagination' => [
            'current_page' => $paginator
                ->currentPage(),

            'last_page' => $paginator
                ->lastPage(),

            'per_page' => $paginator
                ->perPage(),

            'total' => $paginator
                ->total(),

            'from' => $paginator
                ->firstItem(),

            'to' => $paginator
                ->lastItem(),

            'previous_page_url' => $paginator
                ->previousPageUrl(),

            'next_page_url' => $paginator
                ->nextPageUrl(),
            ],

            'filters' => [
            'q' => $search
                ?? '',

            'entity_type' => $entityType
                ?? '',

            'field' => $field
                ?? '',

            'provider' => $provider
                ?? '',

            'review_status' => $reviewStatus
                ?? '',
            ],

            'options' => [
            'entity_types' => $this
                ->entityTypeOptions(),

            'fields' => $this
                ->fieldOptions(),

            'providers' => $this
                ->providerOptions(),

            'review_statuses' => [
                [
                    'value' => '',
                    'label' => 'All review states',
                ],
                [
                    'value' => 'pending',
                    'label' => 'Pending review',
                ],
                [
                    'value' => 'approved',
                    'label' => 'Approved',
                ],
                [
                    'value' => 'rejected',
                    'label' => 'Rejected',
                ],
            ],
            ],
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     pending: int,
     *     approved: int,
     *     rejected: int,
     *     stale_on_page: int
     * }
     */
    private function summary(
        int $staleOnPage,
    ): array {
        $base =
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
                );

        $total =
            (clone $base)
                ->count();

        $pending =
            (clone $base)
                ->whereDoesntHave(
                    'latestReview',
                )
                ->count();

        $approved =
            (clone $base)
                ->whereHas(
                    'latestReview',
                    static function (
                        Builder $query,
                    ): void {
                        $query->where(
                            'decision',
                            AiSeoSuggestionReviewDecision::Approved
                                ->value,
                        );
                    },
                )
                ->count();

        $rejected =
            (clone $base)
                ->whereHas(
                    'latestReview',
                    static function (
                        Builder $query,
                    ): void {
                        $query->where(
                            'decision',
                            AiSeoSuggestionReviewDecision::Rejected
                                ->value,
                        );
                    },
                )
                ->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'stale_on_page' => $staleOnPage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        AiSeoSuggestion $suggestion,
        AiSeoRequest $request,
        AiSeoSuggestionFreshnessData $freshness,
    ): array {
        $entitySnapshot =
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]
            ?? [];

        if (! is_array($entitySnapshot)) {
            $entitySnapshot = [];
        }

        $entity =
            $entitySnapshot[
                'entity'
            ]
            ?? [];

        if (! is_array($entity)) {
            $entity = [];
        }

        $currentSeo =
            $entitySnapshot[
                'current_seo'
            ]
            ?? [];

        if (! is_array($currentSeo)) {
            $currentSeo = [];
        }

        $latestReview =
            $suggestion
                ->latestReview;

        return [
            'id' => $suggestion->id,

            'request_id' => $request->id,

            'entity_type' => $request
                ->entity_type
                ->value,

            'entity_type_label' => $this->entityTypeLabel(
                $request
                    ->entity_type,
            ),

            'entity_id' => $request
                ->entity_id,

            'entity_name' => $this->arrayString(
                $entity,
                'name',
            )
                ?? sprintf(
                    '%s #%d',
                    $this->entityTypeLabel(
                        $request
                            ->entity_type,
                    ),
                    $request
                        ->entity_id,
                ),

            'entity_slug' => $this->arrayString(
                $entity,
                'slug',
            ),

            'field' => $suggestion
                ->field
                ->value,

            'field_label' => $this->fieldLabel(
                $suggestion
                    ->field,
            ),

            'existing_value' => $this->arrayString(
                $currentSeo,
                $suggestion
                    ->field
                    ->value,
            ),

            'suggested_value' => $suggestion
                ->value,

            'rationale' => $suggestion
                ->rationale,

            'review_status' => $latestReview
                ?->decision
                ->value
                ?? 'pending',

            'reviewed_at' => $latestReview
                ?->reviewed_at
                ->toIso8601String(),

            'review_note' => $latestReview
                ?->note,

            'freshness' => $freshness
                ->toArray(),

            'can_approve' => ! $freshness
                ->isStale,

            'can_reject' => true,

            'provider' => $request
                ->provider,

            'model_name' => $request
                ->model_name,

            'prompt_version' => $request
                ->prompt_version,

            'generated_at' => $request
                ->completed_at
                ?->toIso8601String(),

            'requested_by_admin_id' => $request
                ->requested_by_admin_id,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function entityTypeOptions(): array
    {
        $options = [
            [
                'value' => '',
                'label' => 'All entity types',
            ],
        ];

        foreach (
            AiSeoEntityType::cases() as $type
        ) {
            $options[] = [
                'value' => $type->value,
                'label' => $this->entityTypeLabel(
                    $type,
                ).'s',
            ];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function fieldOptions(): array
    {
        $options = [
            [
                'value' => '',
                'label' => 'All suggestion fields',
            ],
        ];

        foreach (
            AiSeoSuggestionField::cases() as $field
        ) {
            $options[] = [
                'value' => $field->value,
                'label' => $this->fieldLabel(
                    $field,
                ),
            ];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function providerOptions(): array
    {
        $providers =
            AiSeoRequest::query()
                ->where(
                    'status',
                    AiSeoRequestStatus::Completed
                        ->value,
                )
                ->whereNotNull(
                    'provider',
                )
                ->where(
                    'provider',
                    '<>',
                    '',
                )
                ->distinct()
                ->orderBy(
                    'provider',
                )
                ->pluck(
                    'provider',
                )
                ->filter(
                    static fn (
                        mixed $provider,
                    ): bool => is_string(
                        $provider,
                    )
                        && trim(
                            $provider,
                        ) !== '',
                )
                ->map(
                    static fn (
                        mixed $provider,
                    ): string => trim(
                        (string) $provider,
                    ),
                )
                ->values()
                ->all();

        $options = [
            [
                'value' => '',
                'label' => 'All providers',
            ],
        ];

        foreach (
            $providers as $provider
        ) {
            $options[] = [
                'value' => $provider,
                'label' => ucfirst(
                    $provider,
                ),
            ];
        }

        return $options;
    }

    private function entityTypeLabel(
        AiSeoEntityType $type,
    ): string {
        return match ($type) {
            AiSeoEntityType::Product => 'Product',
            AiSeoEntityType::Category => 'Category',
            AiSeoEntityType::Brand => 'Brand',
            AiSeoEntityType::Page => 'Page',
        };
    }

    private function fieldLabel(
        AiSeoSuggestionField $field,
    ): string {
        return match ($field) {
            AiSeoSuggestionField::MetaTitle => 'Meta title',
            AiSeoSuggestionField::MetaDescription => 'Meta description',
            AiSeoSuggestionField::OpenGraphTitle => 'Open Graph title',
            AiSeoSuggestionField::OpenGraphDescription => 'Open Graph description',
            AiSeoSuggestionField::TwitterTitle => 'X/Twitter title',
            AiSeoSuggestionField::TwitterDescription => 'X/Twitter description',
        };
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function arrayString(
        array $array,
        string $key,
    ): ?string {
        $value =
            $array[
                $key
            ]
            ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @param  list<string>  $allowed
     */
    private function allowedValue(
        ?string $value,
        array $allowed,
    ): ?string {
        $value =
            $this->nullableString(
                $value,
            );

        if (
            $value === null
            || ! in_array(
                $value,
                $allowed,
                true,
            )
        ) {
            return null;
        }

        return $value;
    }

    private function nullableString(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }
}
