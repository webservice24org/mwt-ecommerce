<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

final class ValidateAndDecrementOrderStockAction
{
    /**
     * This method must run inside the same database
     * transaction that creates the order.
     *
     * @throws ValidationException
     */
    public function execute(
        CartSummaryData $cart,
    ): void {
        foreach ($cart->items as $item) {
            $product = Product::query()
                ->whereKey(
                    $item->productId,
                )
                ->lockForUpdate()
                ->first();

            if ($product === null) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more products are no longer available.',
                ]);
            }

            if (! $product->track_inventory) {
                continue;
            }

            if (
                $product->type ===
                ProductType::Variable
            ) {
                $this->decrementVariantStock(
                    product: $product,
                    variantId: $item->variantId,
                    quantity: $item->quantity,
                );

                continue;
            }

            $this->decrementProductStock(
                product: $product,
                quantity: $item->quantity,
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function decrementProductStock(
        Product $product,
        int $quantity,
    ): void {
        if (
            $product->stock_quantity <
            $quantity
        ) {
            throw ValidationException::withMessages([
                'cart' => sprintf(
                    'Insufficient stock for "%s".',
                    $product->name,
                ),
            ]);
        }

        $product->decrement(
            'stock_quantity',
            $quantity,
        );
    }

    /**
     * @throws ValidationException
     */
    private function decrementVariantStock(
        Product $product,
        ?int $variantId,
        int $quantity,
    ): void {
        if ($variantId === null) {
            throw ValidationException::withMessages([
                'cart' => sprintf(
                    'A product variant is required for "%s".',
                    $product->name,
                ),
            ]);
        }

        $variant = ProductVariant::query()
            ->where(
                'product_id',
                $product->id,
            )
            ->whereKey(
                $variantId,
            )
            ->where(
                'is_active',
                true,
            )
            ->lockForUpdate()
            ->first();

        if ($variant === null) {
            throw ValidationException::withMessages([
                'cart' => sprintf(
                    'The selected variant for "%s" is no longer available.',
                    $product->name,
                ),
            ]);
        }

        if (
            $variant->stock_quantity <
            $quantity
        ) {
            throw ValidationException::withMessages([
                'cart' => sprintf(
                    'Insufficient stock for "%s%s".',
                    $product->name,
                    $variant->name !== null
                    ? ' - '.$variant->name
                    : '',
                ),
            ]);
        }

        $variant->decrement(
            'stock_quantity',
            $quantity,
        );
    }
}
