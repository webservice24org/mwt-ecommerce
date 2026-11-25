<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

use App\Domain\Seo\Ai\Data\AiSeoEntitySnapshotData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;

final readonly class AiSeoEntitySnapshotBuilder
{
    private const BODY_LIMIT =
        6000;

    private const SUMMARY_LIMIT =
        1200;

    private const BUILDER_LIMIT =
        6000;

    public function __construct(
        private AiSeoTextNormalizer $text,
    ) {}

    public function build(
        AiSeoEntityType $entityType,
        int $entityId,
    ): AiSeoEntitySnapshotData {
        return match ($entityType) {
            AiSeoEntityType::Product => $this->product(
                $entityId,
            ),

            AiSeoEntityType::Category => $this->category(
                $entityId,
            ),

            AiSeoEntityType::Brand => $this->brand(
                $entityId,
            ),

            AiSeoEntityType::Page => $this->page(
                $entityId,
            ),
        };
    }

    private function product(
        int $entityId,
    ): AiSeoEntitySnapshotData {
        $product =
            Product::query()
                ->with([
                    'brand:id,name',
                    'categories' => static fn ($query) => $query
                        ->select([
                            'categories.id',
                            'categories.name',
                        ])
                        ->orderBy(
                            'categories.name',
                        ),
                    'seoMeta',
                ])
                ->findOrFail(
                    $entityId,
                );

        return new AiSeoEntitySnapshotData(
            entityType: AiSeoEntityType::Product,

            entityId: $product->id,

            name: $product->name,

            slug: $product->slug,

            content: [
                'summary' => $this->text->normalize(
                    $product
                        ->short_description,
                    self::SUMMARY_LIMIT,
                ),

                'body' => $this->text->normalize(
                    $product
                        ->description,
                    self::BODY_LIMIT,
                ),
            ],

            context: [
                'brand' => $product
                    ->brand
                    ?->name,

                'categories' => $product
                    ->categories
                    ->pluck(
                        'name',
                    )
                    ->values()
                    ->all(),
            ],

            currentSeo: $this->currentSeo(
                $product,
            ),
        );
    }

    private function category(
        int $entityId,
    ): AiSeoEntitySnapshotData {
        $category =
            Category::query()
                ->with([
                    'parent:id,name',
                    'seoMeta',
                ])
                ->findOrFail(
                    $entityId,
                );

        return new AiSeoEntitySnapshotData(
            entityType: AiSeoEntityType::Category,

            entityId: $category->id,

            name: $category->name,

            slug: $category->slug,

            content: [
                'summary' => null,

                'body' => $this->text->normalize(
                    $category
                        ->description,
                    self::BODY_LIMIT,
                ),
            ],

            context: [
                'parent_category' => $category
                    ->parent
                    ?->name,
            ],

            currentSeo: $this->currentSeo(
                $category,
            ),
        );
    }

    private function brand(
        int $entityId,
    ): AiSeoEntitySnapshotData {
        $brand =
            Brand::query()
                ->with([
                    'seoMeta',
                ])
                ->findOrFail(
                    $entityId,
                );

        return new AiSeoEntitySnapshotData(
            entityType: AiSeoEntityType::Brand,

            entityId: $brand->id,

            name: $brand->name,

            slug: $brand->slug,

            content: [
                'summary' => null,

                'body' => $this->text->normalize(
                    $brand
                        ->description,
                    self::BODY_LIMIT,
                ),
            ],

            context: [],

            currentSeo: $this->currentSeo(
                $brand,
            ),
        );
    }

    private function page(
        int $entityId,
    ): AiSeoEntitySnapshotData {
        $page =
            Page::query()
                ->with([
                    'enabledSections',
                    'seoMeta',
                ])
                ->findOrFail(
                    $entityId,
                );

        $builderText =
            $page
                ->enabledSections
                ->map(
                    fn (PageSection $section): ?string => $this
                        ->text
                        ->pageBuilderConfig(
                            $section
                                ->config,
                            1500,
                        ),
                )
                ->filter(
                    static fn (
                        ?string $value,
                    ): bool => $value !== null,
                )
                ->implode(
                    ' | ',
                );

        return new AiSeoEntitySnapshotData(
            entityType: AiSeoEntityType::Page,

            entityId: $page->id,

            name: $page->title,

            slug: $page->slug,

            content: [
                'summary' => null,

                'body' => $this->text->normalize(
                    $page
                        ->content,
                    self::BODY_LIMIT,
                ),

                'builder_text' => $this->text->normalize(
                    $builderText,
                    self::BUILDER_LIMIT,
                ),
            ],

            context: [
                'page_type' => $page
                    ->type
                    ->value,

                'content_mode' => $page
                    ->content_mode
                    ->value,
            ],

            currentSeo: $this->currentSeo(
                $page,
            ),
        );
    }

    /**
     * @return array{
     *     meta_title: string|null,
     *     meta_description: string|null,
     *     og_title: string|null,
     *     og_description: string|null,
     *     twitter_title: string|null,
     *     twitter_description: string|null
     * }
     */
    private function currentSeo(
        Model $entity,
    ): array {
        $seoMeta =
            $entity->relationLoaded(
                'seoMeta',
            )
                ? $entity->getRelation(
                    'seoMeta',
                )
                : null;

        if (
            ! $seoMeta instanceof SeoMeta
        ) {
            $seoMeta = null;
        }

        return [
            'meta_title' => $this->modelString(
                $entity,
                'meta_title',
            ),

            'meta_description' => $this->modelString(
                $entity,
                'meta_description',
            ),

            'og_title' => $this->nullableTrim(
                $seoMeta
                    ?->og_title,
            ),

            'og_description' => $this->nullableTrim(
                $seoMeta
                    ?->og_description,
            ),

            'twitter_title' => $this->nullableTrim(
                $seoMeta
                    ?->twitter_title,
            ),

            'twitter_description' => $this->nullableTrim(
                $seoMeta
                    ?->twitter_description,
            ),
        ];
    }

    private function modelString(
        Model $model,
        string $key,
    ): ?string {
        $value =
            $model->getAttribute(
                $key,
            );

        return is_string(
            $value,
        )
            ? $this->nullableTrim(
                $value,
            )
            : null;
    }

    private function nullableTrim(
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
