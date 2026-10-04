<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Resolvers;

use App\Domain\PageBuilder\Data\ResolvedPageSectionData;
use App\Domain\PageBuilder\Data\SectionLayoutData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\PageSection;
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
}
