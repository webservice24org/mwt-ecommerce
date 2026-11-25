<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Merchant\Data\MerchantFeedBuildResultData;
use App\Domain\Seo\Merchant\Data\MerchantFeedCatalogData;
use App\Domain\Seo\Merchant\Data\MerchantFeedCatalogEntryData;
use App\Domain\Seo\Merchant\Data\MerchantFeedItemData;
use App\Models\Product;
use App\Models\ProductVariant;

final readonly class MerchantProductFeedCatalogBuilder
{
    public const MAX_PRODUCTS =
        1000;

    public const MAX_ITEMS =
        5000;

    public function __construct(
        private MerchantProductDataService $metadata,
        private MerchantProductFeedItemFactory $items,
    ) {}

    public function build(): MerchantFeedCatalogData
    {
        $products =
            Product::query()
                ->published()
                ->with([
                    'brand',
                    'images',

                    'variants' => static function (
                        $query,
                    ): void {
                        $query
                            ->where(
                                'is_active',
                                true,
                            )
                            ->with([
                                'attributeValues.attribute',
                            ])
                            ->orderBy(
                                'position',
                            )
                            ->orderBy(
                                'id',
                            );
                    },
                ])
                ->orderBy(
                    'id',
                )
                ->limit(
                    self::MAX_PRODUCTS
                    + 1,
                )
                ->get();

        $truncated =
            $products->count() >
            self::MAX_PRODUCTS;

        $products =
            $products
                ->take(
                    self::MAX_PRODUCTS,
                )
                ->values();

        $productIds =
            $products
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $id,
                    ): int => (int) $id,
                )
                ->all();

        $metadata =
            $this
                ->metadata
                ->mapForProducts(
                    $productIds,
                );

        /** @var list<MerchantFeedItemData> $items */
        $items = [];

        /** @var list<MerchantFeedCatalogEntryData> $entries */
        $entries = [];

        /** @var list<array{
         *     product_id: int,
         *     variant_id: int|null,
         *     reasons: list<string>
         * }> $excluded
         */
        $excluded = [];

        foreach ($products as $product) {
            if (
                count(
                    $items,
                ) >=
                self::MAX_ITEMS
            ) {
                $truncated =
                    true;

                break;
            }

            if (
                $product->type ===
                ProductType::Simple
            ) {
                $result =
                    $this
                        ->items
                        ->build(
                            product: $product,

                            variant: null,

                            metadata: $metadata[
                                    $this
                                        ->metadata
                                        ->itemKey(
                                            $product,
                                        )
                                ]
                                ?? $this
                                    ->metadata
                                    ->defaults(),
                        );

                $entries[] =
                    new MerchantFeedCatalogEntryData(
                        productId: $product->id,

                        productName: $product->name,

                        variantId: null,

                        variantLabel: null,

                        result: $result,
                    );

                if ($result->item !== null) {
                    $items[] =
                        $result->item;

                    continue;
                }

                $excluded[] = [
                    'product_id' => $product->id,

                    'variant_id' => null,

                    'reasons' => $result
                        ->exclusionReasons,
                ];

                continue;
            }

            $variants =
                $product
                    ->variants
                    ->filter(
                        static fn (
                            ProductVariant $variant,
                        ): bool => $variant
                            ->is_active,
                    )
                    ->values();

            if ($variants->isEmpty()) {
                $result =
                    new MerchantFeedBuildResultData(
                        item: null,

                        exclusionReasons: [
                            'variable_product_has_no_active_variants',
                        ],

                        warnings: [],
                    );

                $entries[] =
                    new MerchantFeedCatalogEntryData(
                        productId: $product->id,

                        productName: $product->name,

                        variantId: null,

                        variantLabel: null,

                        result: $result,
                    );

                $excluded[] = [
                    'product_id' => $product->id,

                    'variant_id' => null,

                    'reasons' => $result
                        ->exclusionReasons,
                ];

                continue;
            }

            foreach ($variants as $variant) {
                if (
                    count(
                        $items,
                    ) >=
                    self::MAX_ITEMS
                ) {
                    $truncated =
                        true;

                    break 2;
                }

                $result =
                    $this
                        ->items
                        ->build(
                            product: $product,

                            variant: $variant,

                            metadata: $metadata[
                                    $this
                                        ->metadata
                                        ->itemKey(
                                            product: $product,

                                            variant: $variant,
                                        )
                                ]
                                ?? $this
                                    ->metadata
                                    ->defaults(),
                        );

                $variantLabel =
                    trim(
                        (string) (
                            $variant->name
                            ?? $variant->sku
                            ?? ''
                        ),
                    );

                $entries[] =
                    new MerchantFeedCatalogEntryData(
                        productId: $product->id,

                        productName: $product->name,

                        variantId: $variant->id,

                        variantLabel: $variantLabel !== ''
                                ? $variantLabel
                                : null,

                        result: $result,
                    );

                if ($result->item !== null) {
                    $items[] =
                        $result->item;

                    continue;
                }

                $excluded[] = [
                    'product_id' => $product->id,

                    'variant_id' => $variant->id,

                    'reasons' => $result
                        ->exclusionReasons,
                ];
            }
        }

        return new MerchantFeedCatalogData(
            items: $items,

            excluded: $excluded,

            truncated: $truncated,

            maxItems: self::MAX_ITEMS,

            entries: $entries,
        );
    }
}
