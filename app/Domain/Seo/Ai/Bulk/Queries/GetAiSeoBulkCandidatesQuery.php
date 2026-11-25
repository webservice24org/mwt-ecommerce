<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Queries;

use App\Domain\Seo\Ai\Bulk\Data\AiSeoBulkCandidateData;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemState;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use JsonException;

final readonly class GetAiSeoBulkCandidatesQuery
{
    private const MAX_CANDIDATES = 250;

    public function __construct(
        private AiSeoEntitySnapshotBuilder $snapshots,
        private AiSeoSnapshotHasher $hasher,
    ) {}

    /**
     * @param  list<string>  $entityTypes
     * @return array{
     *     summary: array{
     *         total: int,
     *         ready: int,
     *         stale: int,
     *         fresh: int,
     *         eligible: int
     *     },
     *     items: list<array<string, mixed>>,
     *     filters: array{
     *         q: string,
     *         entity_types: list<string>,
     *         state: string
     *     },
     *     options: array<string, mixed>
     * }
     *
     * @throws JsonException
     */
    public function handle(
        array $entityTypes = [],
        ?string $search = null,
        ?string $state = null,
    ): array {
        $types = $this->entityTypes($entityTypes);
        $search = $this->nullableString($search);
        $state = in_array(
            $state,
            ['ready', 'stale', 'fresh', 'eligible'],
            true,
        )
            ? $state
            : null;

        $latestRequests = $this->latestCompletedRequests($types);

        $perTypeLimit = max(
            1,
            intdiv(
                self::MAX_CANDIDATES,
                max(1, count($types)),
            ),
        );

        $items = [];

        foreach ($types as $type) {
            foreach (
                $this->models(
                    $type,
                    $search,
                    $perTypeLimit,
                ) as $model
            ) {
                $candidate = $this->inspectModel(
                    $type,
                    $model,
                    $latestRequests,
                );

                if (
                    ! $this->matchesState(
                        $candidate,
                        $state,
                    )
                ) {
                    continue;
                }

                $items[] = $candidate->toArray();
            }
        }

        usort(
            $items,
            static function (
                array $left,
                array $right,
            ): int {
                $type = strcmp(
                    (string) $left['entity_type'],
                    (string) $right['entity_type'],
                );

                if ($type !== 0) {
                    return $type;
                }

                return strcmp(
                    (string) $left['name'],
                    (string) $right['name'],
                );
            },
        );

        $summary = [
            'total' => count($items),
            'ready' => 0,
            'stale' => 0,
            'fresh' => 0,
            'eligible' => 0,
        ];

        foreach ($items as $item) {
            $itemState = (string) $item['state'];

            if (array_key_exists($itemState, $summary)) {
                $summary[$itemState]++;
            }

            if ((bool) $item['eligible']) {
                $summary['eligible']++;
            }
        }

        return [
            'summary' => $summary,
            'items' => $items,
            'filters' => [
                'q' => $search ?? '',
                'entity_types' => array_map(
                    static fn (
                        AiSeoEntityType $type,
                    ): string => $type->value,
                    $types,
                ),
                'state' => $state ?? '',
            ],
            'options' => [
                'entity_types' => array_map(
                    fn (
                        AiSeoEntityType $type,
                    ): array => [
                        'value' => $type->value,
                        'label' => $this->typeLabel($type),
                    ],
                    AiSeoEntityType::cases(),
                ),
                'states' => [
                    [
                        'value' => '',
                        'label' => 'All states',
                    ],
                    [
                        'value' => 'eligible',
                        'label' => 'Eligible — ready or stale',
                    ],
                    [
                        'value' => 'ready',
                        'label' => 'Ready — no prior suggestions',
                    ],
                    [
                        'value' => 'stale',
                        'label' => 'Stale — source changed',
                    ],
                    [
                        'value' => 'fresh',
                        'label' => 'Fresh — current suggestions exist',
                    ],
                ],
                'max_candidates' => self::MAX_CANDIDATES,
            ],
        ];
    }

    /**
     * @throws JsonException
     * @throws ModelNotFoundException
     */
    public function inspect(
        AiSeoEntityType $entityType,
        int $entityId,
    ): AiSeoBulkCandidateData {
        $model = $this->model(
            $entityType,
            $entityId,
        );

        $latest = AiSeoRequest::query()
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
                AiSeoRequestStatus::Completed->value,
            )
            ->orderByDesc('id')
            ->first();

        return $this->candidate(
            $entityType,
            $model,
            $latest,
        );
    }

    /**
     * @param  list<string>  $values
     * @return list<AiSeoEntityType>
     */
    private function entityTypes(
        array $values,
    ): array {
        if ($values === []) {
            return AiSeoEntityType::cases();
        }

        $types = [];

        foreach ($values as $value) {
            $type = AiSeoEntityType::tryFrom($value);

            if (
                $type !== null
                && ! in_array(
                    $type,
                    $types,
                    true,
                )
            ) {
                $types[] = $type;
            }
        }

        return $types === []
            ? AiSeoEntityType::cases()
            : $types;
    }

    /**
     * @return Collection<int, Model>
     */
    private function models(
        AiSeoEntityType $type,
        ?string $search,
        int $limit,
    ): Collection {
        $query = match ($type) {
            AiSeoEntityType::Product => Product::query(),
            AiSeoEntityType::Category => Category::query(),
            AiSeoEntityType::Brand => Brand::query(),
            AiSeoEntityType::Page => Page::query(),
        };

        if ($search !== null) {
            $column = $type === AiSeoEntityType::Page
                ? 'title'
                : 'name';

            $query->where(
                $column,
                'like',
                '%'.$search.'%',
            );
        }

        /** @var Collection<int, Model> $models */
        $models = $query
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return $models;
    }

    /**
     * @throws ModelNotFoundException
     */
    private function model(
        AiSeoEntityType $type,
        int $entityId,
    ): Model {
        return match ($type) {
            AiSeoEntityType::Product => Product::query()
                ->findOrFail($entityId),

            AiSeoEntityType::Category => Category::query()
                ->findOrFail($entityId),

            AiSeoEntityType::Brand => Brand::query()
                ->findOrFail($entityId),

            AiSeoEntityType::Page => Page::query()
                ->findOrFail($entityId),
        };
    }

    /**
     * @param  list<AiSeoEntityType>  $types
     * @return array<string, AiSeoRequest>
     */
    private function latestCompletedRequests(
        array $types,
    ): array {
        $values = array_map(
            static fn (
                AiSeoEntityType $type,
            ): string => $type->value,
            $types,
        );

        $ids = AiSeoRequest::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn(
                'entity_type',
                $values,
            )
            ->where(
                'status',
                AiSeoRequestStatus::Completed->value,
            )
            ->groupBy(
                'entity_type',
                'entity_id',
            )
            ->pluck('id')
            ->map(
                static fn (
                    mixed $id,
                ): int => (int) $id,
            )
            ->all();

        if ($ids === []) {
            return [];
        }

        $requests = AiSeoRequest::query()
            ->whereIn('id', $ids)
            ->get();

        $indexed = [];

        foreach ($requests as $request) {
            $indexed[
                $request->entity_type->value
                .':'
                .$request->entity_id
            ] = $request;
        }

        return $indexed;
    }

    /**
     * @param  array<string, AiSeoRequest>  $latestRequests
     *
     * @throws JsonException
     */
    private function inspectModel(
        AiSeoEntityType $type,
        Model $model,
        array $latestRequests,
    ): AiSeoBulkCandidateData {
        $key = $type->value
            .':'
            .$model->getKey();

        return $this->candidate(
            $type,
            $model,
            $latestRequests[$key] ?? null,
        );
    }

    /**
     * @throws JsonException
     */
    private function candidate(
        AiSeoEntityType $type,
        Model $model,
        ?AiSeoRequest $latestRequest,
    ): AiSeoBulkCandidateData {
        $entityId = (int) $model->getKey();

        $snapshot = $this
            ->snapshots
            ->build(
                $type,
                $entityId,
            )
            ->toArray();

        /** @var array{
         *     name: string,
         *     slug: string
         * } $entity
         */
        $entity = $snapshot['entity'];

        $currentHash = $this
            ->hasher
            ->hash($snapshot);

        if ($latestRequest === null) {
            return new AiSeoBulkCandidateData(
                entityType: $type,
                entityId: $entityId,
                name: $entity['name'],
                slug: $entity['slug'],
                currentSnapshotHash: $currentHash,
                latestRequestId: null,
                latestRequestSnapshotHash: null,
                state: AiSeoBulkItemState::Ready,
                reason: 'No completed AI metadata suggestion request exists.',
            );
        }

        $sourceSnapshot = $latestRequest
            ->input_snapshot[
                'entity_snapshot'
            ]
            ?? null;

        if (! is_array($sourceSnapshot)) {
            return new AiSeoBulkCandidateData(
                entityType: $type,
                entityId: $entityId,
                name: $entity['name'],
                slug: $entity['slug'],
                currentSnapshotHash: $currentHash,
                latestRequestId: $latestRequest->id,
                latestRequestSnapshotHash: null,
                state: AiSeoBulkItemState::Stale,
                reason: 'Latest completed request has no comparable entity snapshot.',
            );
        }

        $requestHash = $this
            ->hasher
            ->hash($sourceSnapshot);

        $fresh = hash_equals(
            $currentHash,
            $requestHash,
        );

        return new AiSeoBulkCandidateData(
            entityType: $type,
            entityId: $entityId,
            name: $entity['name'],
            slug: $entity['slug'],
            currentSnapshotHash: $currentHash,
            latestRequestId: $latestRequest->id,
            latestRequestSnapshotHash: $requestHash,
            state: $fresh
                ? AiSeoBulkItemState::Fresh
                : AiSeoBulkItemState::Stale,
            reason: $fresh
                ? 'Current entity snapshot already has completed AI metadata suggestions.'
                : 'Entity changed after the latest completed AI metadata suggestion request.',
        );
    }

    private function matchesState(
        AiSeoBulkCandidateData $candidate,
        ?string $state,
    ): bool {
        if ($state === null) {
            return true;
        }

        if ($state === 'eligible') {
            return $candidate
                ->state
                ->isEligibleForGeneration();
        }

        return $candidate
            ->state
            ->value === $state;
    }

    private function nullableString(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function typeLabel(
        AiSeoEntityType $type,
    ): string {
        return match ($type) {
            AiSeoEntityType::Product => 'Products',
            AiSeoEntityType::Category => 'Categories',
            AiSeoEntityType::Brand => 'Brands',
            AiSeoEntityType::Page => 'Pages',
        };
    }
}
