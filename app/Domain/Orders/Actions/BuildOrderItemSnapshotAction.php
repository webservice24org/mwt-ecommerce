<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Cart\Data\CartItemData;
use App\Domain\Orders\Data\OrderItemSnapshotData;
use App\Models\Product;
use App\Models\ProductVariant;
use LogicException;

final class BuildOrderItemSnapshotAction
{
    public function execute(
        CartItemData $item,
    ): OrderItemSnapshotData {
        $product = Product::query()
            ->find(
                $item->productId,
            );

        if ($product === null) {
            throw new LogicException(
                'Cannot create an order item snapshot for a missing product.',
            );
        }

        $variant = null;

        if ($item->variantId !== null) {
            $variant = ProductVariant::query()
                ->where(
                    'product_id',
                    $product->id,
                )
                ->whereKey(
                    $item->variantId,
                )
                ->first();

            if ($variant === null) {
                throw new LogicException(
                    'Cannot create an order item snapshot for a missing product variant.',
                );
            }
        }

        $unitPrice = (int) round(
            $item->unitPrice,
        );

        $lineTotal = (int) round(
            $item->lineTotal,
        );

        return new OrderItemSnapshotData(
            productId: $product->id,

            variantId: $variant?->id,

            productName: $product->name,

            sku: $variant !== null
            ? $variant->sku
            : $product->sku,

            variantDescription: $variant?->name,

            unitPrice: $unitPrice,

            quantity: $item->quantity,

            stockWasDecremented: $product->track_inventory,

            lineTotal: $lineTotal,
        );
    }
}
