<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Queries;

use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\GenerativeSearch\Data\GenerativeSearchFindingData;
use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchReadinessStatus;
use App\Domain\Seo\GenerativeSearch\Services\GenerativeSearchReadinessAnalyzer;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Services\StorefrontSeoResolver;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use InvalidArgumentException;

final readonly class GetGenerativeSearchReadinessWorkspaceQuery
{
    public const SCAN_LIMIT = 250;

    private const DEFAULT_PER_PAGE = 25;

    public function __construct(
        private AiSeoEntitySnapshotBuilder $snapshots,
        private StorefrontSeoResolver $seo,
        private SeoSettingsService $seoSettings,
        private CanonicalUrlResolver $canonical,
        private GenerativeSearchReadinessAnalyzer $readiness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(
        string $entityType = 'product',
        ?string $status = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
    ): array {
        $type = AiSeoEntityType::tryFrom($entityType);

        if ($type === null) {
            throw new InvalidArgumentException(
                'Unsupported generative-search entity type.',
            );
        }

        $statusFilter =
            $status !== null && $status !== ''
                ? GenerativeSearchReadinessStatus::tryFrom($status)
                : null;

        if (
            $status !== null
            && $status !== ''
            && $statusFilter === null
        ) {
            throw new InvalidArgumentException(
                'Unsupported generative-search readiness status.',
            );
        }

        $search = $this->normalizeSearch($search);
        $page = max(1, $page);

        $perPage = in_array($perPage, [10, 25, 50], true)
            ? $perPage
            : self::DEFAULT_PER_PAGE;

        $candidateIds = $this->candidateIds(
            type: $type,
            search: $search,
        );

        $truncated = count($candidateIds) > self::SCAN_LIMIT;

        $candidateIds = array_slice(
            $candidateIds,
            0,
            self::SCAN_LIMIT,
        );

        $settings = $this->seoSettings->get();

        $rows = [];

        $summary = [
            'scanned' => 0,
            'ready' => 0,
            'needs_work' => 0,
            'blocked' => 0,
            'truncated' => $truncated,
            'scan_limit' => self::SCAN_LIMIT,
        ];

        foreach ($candidateIds as $entityId) {
            $model = $this->model(
                type: $type,
                entityId: $entityId,
            );

            $snapshot = $this->snapshots
                ->build(
                    $type,
                    $entityId,
                )
                ->toArray();

            $resolvedSeo = $this->seo->resolve(
                entity: $model,
                settings: $settings,
            );

            $canonicalUrl = $this->canonical->resolve(
                canonicalOverride: $resolvedSeo->canonicalOverride,
                path: $this->publicPath(
                    type: $type,
                    slug: $model->slug,
                ),
                canonicalHost: $settings->canonicalHost,
            );

            $result = $this->readiness->analyze(
                entitySnapshot: $snapshot,
                resolvedSeo: $resolvedSeo,
                canonicalUrl: $canonicalUrl,
                structuredDataTypes: $this->structuredDataTypes(
                    type: $type,
                    model: $model,
                    enabled: $settings->enableStructuredData,
                ),
                hasPrimaryImage: $this->hasPrimaryImage($model),
            );

            $summary['scanned']++;
            $summary[$result->status->value]++;

            if (
                $statusFilter !== null
                && $result->status !== $statusFilter
            ) {
                continue;
            }

            $rows[] = [
                'entity_type' => $type->value,
                'entity_id' => $entityId,
                'name' => $snapshot['entity']['name'],
                'slug' => $snapshot['entity']['slug'],
                'public_path' => $this->publicPath(
                    type: $type,
                    slug: $snapshot['entity']['slug'],
                ),
                'status' => $result->status->value,
                'eligible_for_indexing' => $result->eligibleForIndexing,
                'signals' => $result->signals,
                'findings' => array_map(
                    static fn (
                        GenerativeSearchFindingData $finding,
                    ): array => $finding->toArray(),
                    $result->findings,
                ),
            ];
        }

        $total = count($rows);

        $lastPage = max(
            1,
            (int) ceil($total / $perPage),
        );

        $page = min($page, $lastPage);

        $rows = array_slice(
            $rows,
            ($page - 1) * $perPage,
            $perPage,
        );

        return [
            'filters' => [
                'entity_type' => $type->value,
                'status' => $statusFilter?->value,
                'search' => $search,
                'page' => $page,
                'per_page' => $perPage,
            ],
            'options' => [
                'entity_types' => [
                    ['value' => 'product', 'label' => 'Products'],
                    ['value' => 'category', 'label' => 'Categories'],
                    ['value' => 'brand', 'label' => 'Brands'],
                    ['value' => 'page', 'label' => 'Pages'],
                ],
                'statuses' => [
                    [
                        'value' => GenerativeSearchReadinessStatus::Ready->value,
                        'label' => 'Ready',
                    ],
                    [
                        'value' => GenerativeSearchReadinessStatus::NeedsWork->value,
                        'label' => 'Needs work',
                    ],
                    [
                        'value' => GenerativeSearchReadinessStatus::Blocked->value,
                        'label' => 'Blocked',
                    ],
                ],
            ],
            'summary' => $summary,
            'rows' => $rows,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function candidateIds(
        AiSeoEntityType $type,
        ?string $search,
    ): array {
        $limit = self::SCAN_LIMIT + 1;

        return match ($type) {
            AiSeoEntityType::Product => Product::query()
                ->published()
                ->when(
                    $search !== null,
                    static fn ($query) => $query->where(
                        static function ($query) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    '%'.$search.'%',
                                );
                        },
                    ),
                )
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int => (int) $id,
                )
                ->all(),

            AiSeoEntityType::Category => Category::query()
                ->where('is_active', true)
                ->when(
                    $search !== null,
                    static fn ($query) => $query->where(
                        static function ($query) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    '%'.$search.'%',
                                );
                        },
                    ),
                )
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int => (int) $id,
                )
                ->all(),

            AiSeoEntityType::Brand => Brand::query()
                ->where('is_active', true)
                ->when(
                    $search !== null,
                    static fn ($query) => $query->where(
                        static function ($query) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    '%'.$search.'%',
                                );
                        },
                    ),
                )
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int => (int) $id,
                )
                ->all(),

            AiSeoEntityType::Page => Page::query()
                ->published()
                ->when(
                    $search !== null,
                    static fn ($query) => $query->where(
                        static function ($query) use ($search): void {
                            $query
                                ->where(
                                    'title',
                                    'like',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    '%'.$search.'%',
                                );
                        },
                    ),
                )
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int => (int) $id,
                )
                ->all(),
        };
    }

    private function model(
        AiSeoEntityType $type,
        int $entityId,
    ): Product|Category|Brand|Page {
        return match ($type) {
            AiSeoEntityType::Product => Product::query()->findOrFail($entityId),

            AiSeoEntityType::Category => Category::query()->findOrFail($entityId),

            AiSeoEntityType::Brand => Brand::query()->findOrFail($entityId),

            AiSeoEntityType::Page => Page::query()->findOrFail($entityId),
        };
    }

    private function publicPath(
        AiSeoEntityType $type,
        string $slug,
    ): string {
        return match ($type) {
            AiSeoEntityType::Product => '/products/'.$slug,

            AiSeoEntityType::Category => '/category/'.$slug,

            AiSeoEntityType::Brand => '/brand/'.$slug,

            AiSeoEntityType::Page => '/pages/'.$slug,
        };
    }

    /**
     * @return list<string>
     */
    private function structuredDataTypes(
        AiSeoEntityType $type,
        Product|Category|Brand|Page $model,
        bool $enabled,
    ): array {
        if (! $enabled) {
            return [];
        }

        return match ($type) {
            AiSeoEntityType::Product => [
                'BreadcrumbList',
                $model instanceof Product
                && $model->type === ProductType::Variable
                    ? 'ProductGroup'
                    : 'Product',
            ],

            AiSeoEntityType::Category,
            AiSeoEntityType::Brand => [
                'BreadcrumbList',
            ],

            AiSeoEntityType::Page => $model instanceof Page
                && (bool) $model->getAttribute('show_breadcrumbs')
                    ? ['BreadcrumbList']
                    : [],
        };
    }

    private function hasPrimaryImage(
        Product|Category|Brand|Page $model,
    ): bool {
        if ($model instanceof Product) {
            return $model
                ->images()
                ->exists();
        }

        $value = match (true) {
            $model instanceof Category => $model->image_path,

            $model instanceof Brand => $model->logo_path,

            $model instanceof Page => $model->featured_image,
        };

        return is_string($value)
            && trim($value) !== '';
    }

    private function normalizeSearch(
        ?string $search,
    ): ?string {
        if ($search === null) {
            return null;
        }

        $search = trim($search);

        return $search !== ''
            ? mb_substr(
                $search,
                0,
                120,
            )
            : null;
    }
}
