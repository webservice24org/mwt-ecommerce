<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Queries;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Domain\Wishlist\Services\WishlistService;
use App\Models\Product;
use App\Models\User;

final readonly class StorefrontWishlistQuery
{
    public function __construct(
        private WishlistService $wishlist,
        private StorefrontProductDataFactory $productData,
    ) {}

    /**
     * @return list<array{
     *     product: array<string, mixed>,
     *     availability: 'available'|'requires_options'|'unavailable',
     *     availability_label: string,
     *     can_add_to_cart: bool
     * }>
     */
    public function get(
        ?User $user = null,
    ): array {
        $ids =
            $this->wishlist->ids(
                user: $user,
            );

        if ($ids === []) {
            return [];
        }

        return Product::query()
            /*
             * Intentionally do NOT call ->published().
             *
             * Wishlist must retain stale/draft/future
             * products so the customer can see them as
             * "Unavailable" and remove them.
             */
            ->whereIn(
                'id',
                $ids,
            )
            ->with([
                'brand:id,name,slug',

                'images' => static fn (
                    $query,
                ) => $query
                    ->orderByDesc(
                        'is_primary',
                    )
                    ->orderBy(
                        'position',
                    )
                    ->orderBy(
                        'id',
                    ),

                'variants' => static fn (
                    $query,
                ) => $query
                    ->where(
                        'is_active',
                        true,
                    )
                    ->select([
                        'id',
                        'product_id',
                        'price',
                        'compare_at_price',
                        'cost_price',
                        'is_active',
                    ]),
            ])
            ->get()
            ->sortBy(
                static fn (
                    Product $product,
                ): int => array_search(
                    $product->id,
                    $ids,
                    true,
                ),
            )
            ->map(
                function (Product $product): array {
                    $published =
                        $product->status ===
                        ProductStatus::Published
                        && (
                            $product->published_at ===
                            null
                            || $product
                                ->published_at
                                ->lte(
                                    now(),
                                )
                        );

                    $isVariable =
                        $product
                            ->type
                            ->value ===
                        'variable';

                    $availability =
                        ! $published
                        ? 'unavailable'
                        : (
                            $isVariable
                            ? 'requires_options'
                            : 'available'
                        );

                    $availabilityLabel =
                        match (
                            $availability
                        ) {
                            'available' => 'Available',

                            'requires_options' => 'Choose options',

                            default => 'Unavailable',
                        };

                    return [
                        'product' => $this
                            ->productData
                            ->card(
                                $product,
                            )
                            ->toArray(),

                        'availability' => $availability,

                        'availability_label' => $availabilityLabel,

                        'can_add_to_cart' => $availability ===
                            'available',
                    ];
                },
            )
            ->values()
            ->all();
    }
}
