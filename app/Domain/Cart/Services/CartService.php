<?php

declare(strict_types=1);

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Data\CartItemData;
use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Catalog\Services\ProductPricingResolver;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final readonly class CartService
{
    private const SESSION_KEY = 'storefront.cart';

    public function __construct(
        private ProductPricingResolver $pricingResolver,
    ) {}

    public function add(
        Product $product,
        ?ProductVariant $variant = null,
        int $quantity = 1,
    ): CartSummaryData {
        $quantity = max(
            1,
            $quantity,
        );

        $key = $this->key(
            $product->id,
            $variant?->id,
        );

        $items = $this->rawItems();

        $currentQuantity = isset(
            $items[$key],
        )
            ? $items[$key]['quantity']
            : 0;

        $items[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $currentQuantity + $quantity,
        ];

        $this->putRawItems(
            $items,
        );

        return $this->summary();
    }

    public function update(
        string $key,
        int $quantity,
    ): CartSummaryData {
        $items = $this->rawItems();

        if (! isset($items[$key])) {
            return $this->summary();
        }

        if ($quantity <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['quantity'] = $quantity;
        }

        $this->putRawItems(
            $items,
        );

        return $this->summary();
    }

    public function remove(
        string $key,
    ): CartSummaryData {
        $items = $this->rawItems();

        unset($items[$key]);

        $this->putRawItems(
            $items,
        );

        return $this->summary();
    }

    public function clear(): CartSummaryData
    {
        session()->forget(
            self::SESSION_KEY,
        );

        return $this->summary();
    }

    public function count(): int
    {
        return array_sum(
            array_map(
                static fn (array $item): int => max(
                    0,
                    $item['quantity'],
                ),
                $this->rawItems(),
            ),
        );
    }

    public function summary(): CartSummaryData
    {
        $rawItems = $this->rawItems();

        if ($rawItems === []) {
            return new CartSummaryData(
                items: [],
                itemCount: 0,
                subtotal: 0.0,
            );
        }

        $productIds = collect(
            $rawItems,
        )
            ->pluck(
                'product_id',
            )
            ->unique()
            ->values();

        $products = Product::query()
            ->published()
            ->whereIn(
                'id',
                $productIds,
            )
            ->with([
                'images' => static fn ($query) => $query
                    ->orderByDesc(
                        'is_primary',
                    )
                    ->orderBy(
                        'position',
                    )
                    ->orderBy(
                        'id',
                    ),

                'variants' => static fn ($query) => $query
                    ->where(
                        'is_active',
                        true,
                    ),
            ])
            ->get()
            ->keyBy('id');

        $resolvedItems = [];

        foreach (
            $rawItems as $key => $raw
        ) {
            $product = $products->get(
                $raw['product_id'],
            );

            if (! $product instanceof Product) {
                continue;
            }

            $variant = null;

            if ($raw['variant_id'] !== null) {
                $variant = $product
                    ->variants
                    ->firstWhere(
                        'id',
                        $raw['variant_id'],
                    );

                if (! $variant instanceof ProductVariant) {
                    continue;
                }
            }

            $quantity = max(
                1,
                $raw['quantity'],
            );

            $pricing = $this
                ->pricingResolver
                ->resolve(
                    product: $product,
                    variant: $variant,
                );

            if ($pricing->price === null) {
                continue;
            }

            $unitPrice = (float) $pricing->price;

            $image = $product
                ->images
                ->first();

            $imageUrl = null;

            if ($image !== null) {
                /** @var FilesystemAdapter $disk */
                $disk = Storage::disk(
                    'public',
                );

                $imageUrl = $disk->url(
                    $image->path,
                );
            }

            $resolvedItems[] = new CartItemData(
                key: (string) $key,
                productId: $product->id,
                variantId: $variant?->id,
                quantity: $quantity,
                name: $product->name,
                slug: $product->slug,
                variantName: $variant?->name,
                imageUrl: $imageUrl,
                unitPrice: $unitPrice,
                lineTotal: $unitPrice * $quantity,
            );
        }

        $itemCount = array_sum(
            array_map(
                static fn (CartItemData $item): int => $item->quantity,
                $resolvedItems,
            ),
        );

        $subtotal = array_sum(
            array_map(
                static fn (CartItemData $item): float => $item->lineTotal,
                $resolvedItems,
            ),
        );

        return new CartSummaryData(
            items: $resolvedItems,
            itemCount: $itemCount,
            subtotal: $subtotal,
        );
    }

    /**
     * @return array<string, array{
     *     product_id: int,
     *     variant_id: int|null,
     *     quantity: int
     * }>
     */
    private function rawItems(): array
    {
        $items = session()->get(
            self::SESSION_KEY,
            [],
        );

        if (! is_array($items)) {
            return [];
        }

        return $items;
    }

    /**
     * @param  array<string, array{
     *     product_id: int,
     *     variant_id: int|null,
     *     quantity: int
     * }>  $items
     */
    private function putRawItems(
        array $items,
    ): void {
        session()->put(
            self::SESSION_KEY,
            $items,
        );
    }

    private function key(
        int $productId,
        ?int $variantId,
    ): string {
        if ($variantId !== null) {
            return sprintf(
                'product:%d:variant:%d',
                $productId,
                $variantId,
            );
        }

        return sprintf(
            'product:%d',
            $productId,
        );
    }
}
