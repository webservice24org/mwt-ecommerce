<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Services;

use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Models\Category;
use App\Models\Product;

final readonly class CatalogSourceReferenceValidator
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(
        SectionType $sectionType,
        array $config,
    ): void {
        if (
            ! in_array(
                $sectionType,
                [
                    SectionType::FeaturedProducts,
                    SectionType::ProductCollection,
                ],
                true,
            )
        ) {
            return;
        }

        $source = $config['source'] ?? null;

        if (! is_array($source)) {
            return;
        }

        $sourceType = CatalogSourceType::tryFrom(
            $source['type'] ?? '',
        );

        if ($sourceType === null) {
            return;
        }

        match ($sourceType) {
            CatalogSourceType::Featured,
            CatalogSourceType::Latest => null,

            CatalogSourceType::Category => $this->validateCategory(
                $source,
            ),

            CatalogSourceType::Manual => $this->validateProducts(
                $source,
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function validateCategory(
        array $source,
    ): void {
        $categoryId =
            $source['category_id'] ?? null;

        /*
         * Structural validation has already guaranteed this,
         * but retaining the guard keeps this service safe when
         * called independently.
         */
        if (
            ! is_int($categoryId) ||
            $categoryId < 1
        ) {
            return;
        }

        $exists = Category::query()
            ->whereKey($categoryId)
            ->exists();

        if (! $exists) {
            throw new InvalidSectionConfiguration([
                'source.category_id' => [
                    'The selected category does not exist.',
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function validateProducts(
        array $source,
    ): void {
        $productIds =
            $source['product_ids'] ?? null;

        if (! is_array($productIds)) {
            return;
        }

        /** @var list<int> $productIds */
        $existingIds = Product::query()
            ->whereIn(
                'id',
                $productIds,
            )
            ->pluck('id')
            ->map(
                static fn (mixed $id): int => (int) $id,
            )
            ->all();

        $existingLookup =
            array_fill_keys(
                $existingIds,
                true,
            );

        $missingIds = [];

        foreach (
            $productIds as $productId
        ) {
            if (
                ! isset(
                    $existingLookup[
                        $productId
                    ],
                )
            ) {
                $missingIds[] =
                    $productId;
            }
        }

        if ($missingIds === []) {
            return;
        }

        throw new InvalidSectionConfiguration([
            'source.product_ids' => [
                sprintf(
                    'The following selected products do not exist: %s.',
                    implode(
                        ', ',
                        $missingIds,
                    ),
                ),
            ],
        ]);
    }
}
