<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Resolvers;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Data\ResolvedPageSectionData;
use App\Domain\PageBuilder\Data\SectionLayoutData;
use App\Domain\PageBuilder\Enums\BrandSourceType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Brand;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use LogicException;

final readonly class PageSectionResolver
{
    public function __construct(
        private FeaturedProductsResolver $featuredProducts,
        private ProductCategoriesResolver $productCategories,
        private ProductCollectionResolver $productCollection,
    ) {}

    public function resolve(
        PageSection $section,
    ): ResolvedPageSectionData {
        $data = match ($section->type) {
            SectionType::Hero,
            SectionType::PromotionalBanner => [],

            SectionType::Content => [],

            SectionType::CallToAction => [],

            SectionType::FeaturesBenefits => [],

            SectionType::Testimonials => [],

            SectionType::Faq => [],

            SectionType::Brands => $this->resolveBrands(
                $section,
            ),

            SectionType::FeaturedProducts => [
                'products' => $this
                    ->featuredProducts
                    ->resolve(
                        $section->config,
                    ),
            ],

            SectionType::ProductCategories => [
                'categories' => $this
                    ->productCategories
                    ->resolve(
                        $section->config,
                    ),
            ],

            SectionType::ProductCollection => [
                'products' => $this
                    ->productCollection
                    ->resolve(
                        $section->config,
                    ),
            ],

            default => throw new LogicException(
                sprintf(
                    'No public resolver is registered for section type [%s].',
                    $section->type->value,
                ),
            ),
        };

        return new ResolvedPageSectionData(
            id: $section->id,
            type: $section->type,
            template: $section->template,
            config: $section->config,
            layout: SectionLayoutData::fromArray(
                $section->layout,
            ),
            data: $data,
        );
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     logo_url: string|null,
     *     product_count: int
     * }>
     */
    private function resolveBrands(
        PageSection $section,
    ): array {
        $config =
            $section->config;

        $source =
            $config['source'] ??
            null;

        /*
         * Public resolution fails closed.
         *
         * Saved configuration should already have
         * passed BrandConfigSchema, but legacy or
         * corrupted database data must never turn
         * into an implicit "all brands" source.
         */
        if (
            ! is_array(
                $source,
            )
        ) {
            return [];
        }

        $sourceTypeValue =
            $source['type'] ??
            null;

        if (
            ! is_string(
                $sourceTypeValue,
            )
        ) {
            return [];
        }

        $sourceType =
            BrandSourceType::tryFrom(
                $sourceTypeValue,
            );

        if (
            $sourceType ===
            null
        ) {
            return [];
        }

        $limitValue =
            $config['limit'] ??
            null;

        /*
         * The schema allows 1..24. Keep a
         * defensive fallback and clamp here
         * for legacy/corrupted records.
         */
        $limit =
            is_int(
                $limitValue,
            )
            ? max(
                1,
                min(
                    24,
                    $limitValue,
                ),
            )
            : 12;

        return match (
            $sourceType
        ) {
            BrandSourceType::All => $this->resolveAllBrands(
                $limit,
            ),

            BrandSourceType::Manual => $this->resolveManualBrands(
                source: $source,
                limit: $limit,
            ),
        };
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     logo_url: string|null,
     *     product_count: int
     * }>
     */
    private function resolveAllBrands(
        int $limit,
    ): array {
        return Brand::query()
            ->where(
                'is_active',
                true,
            )
            ->select([
                'id',
                'name',
                'slug',
                'description',
                'logo_path',
            ])
            ->withCount([
                'products as published_products_count' => static function (Builder $query): void {
                    $query
                        ->where(
                            'status',
                            ProductStatus::Published->value,
                        )
                        ->where(
                            static function (Builder $query): void {
                                $query
                                    ->whereNull(
                                        'published_at',
                                    )
                                    ->orWhere(
                                        'published_at',
                                        '<=',
                                        now(),
                                    );
                            },
                        );
                },
            ])
            ->orderBy(
                'position',
            )
            ->orderBy(
                'name',
            )
            ->orderBy(
                'id',
            )
            ->limit(
                $limit,
            )
            ->get()
            ->map(
                fn (
                    Brand $brand,
                ): array => $this->brandData(
                    $brand,
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @param  array<mixed>  $source
     * @return list<array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     logo_url: string|null,
     *     product_count: int
     * }>
     */
    private function resolveManualBrands(
        array $source,
        int $limit,
    ): array {
        $rawIds =
            $source[
                'brand_ids'
            ] ?? [];

        if (
            ! is_array(
                $rawIds,
            ) ||
            ! array_is_list(
                $rawIds,
            )
        ) {
            return [];
        }

        $ids = [];

        foreach (
            $rawIds as $rawId
        ) {
            if (
                ! is_int(
                    $rawId,
                ) ||
                $rawId < 1 ||
                in_array(
                    $rawId,
                    $ids,
                    true,
                )
            ) {
                continue;
            }

            $ids[] =
                $rawId;

            /*
             * The Brand schema already allows
             * at most 24 manual selections.
             *
             * Keep this defensive ceiling here,
             * but do NOT stop at the storefront
             * display limit yet because inactive
             * or deleted brands may be skipped.
             */
            if (
                count(
                    $ids,
                ) >= 24
            ) {
                break;
            }
        }

        if ($ids === []) {
            return [];
        }

        $brands =
            Brand::query()
                ->where(
                    'is_active',
                    true,
                )
                ->whereIn(
                    'id',
                    $ids,
                )
                ->select([
                    'id',
                    'name',
                    'slug',
                    'description',
                    'logo_path',
                ])
                ->withCount([
                    'products as published_products_count' => static function (Builder $query): void {
                        $query
                            ->where(
                                'status',
                                ProductStatus::Published->value,
                            )
                            ->where(
                                static function (Builder $query): void {
                                    $query
                                        ->whereNull(
                                            'published_at',
                                        )
                                        ->orWhere(
                                            'published_at',
                                            '<=',
                                            now(),
                                        );
                                },
                            );
                    },
                ])
                ->get()
                ->keyBy(
                    'id',
                );

        $resolved = [];

        foreach (
            $ids as $id
        ) {
            $brand =
                $brands->get(
                    $id,
                );

            if (
                ! $brand instanceof Brand
            ) {
                continue;
            }

            $resolved[] =
                $this->brandData(
                    $brand,
                );

            /*
             * Apply the section limit to
             * successfully resolved active
             * brands, not raw configured IDs.
             */
            if (
                count(
                    $resolved,
                ) >= $limit
            ) {
                break;
            }
        }

        return $resolved;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     logo_url: string|null,
     *     product_count: int
     * }
     */
    private function brandData(
        Brand $brand,
    ): array {
        return [
            'id' => $brand->id,

            'name' => $brand->name,

            'slug' => $brand->slug,

            'description' => $brand->description,

            'logo_url' => $this->brandLogoUrl(
                $brand->logo_path,
            ),

            'product_count' => (int) $brand->getAttribute(
                'published_products_count',
            ),
        ];
    }

    private function brandLogoUrl(
        ?string $path,
    ): ?string {
        if (
            $path === null ||
            $path === ''
        ) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public',
            );

        return $disk->url(
            $path,
        );
    }
}
