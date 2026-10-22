<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

final class RestoreCancelledOrderStockAction
{
    /**
     * Must execute inside the cancellation transaction.
     *
     * @throws ValidationException
     */
    public function execute(
        Order $order,
    ): void {
        $items = $order
            ->items()
            ->orderBy(
                'id',
            )
            ->get();

        foreach ($items as $item) {
            $this->restoreItem(
                $item,
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function restoreItem(
        OrderItem $item,
    ): void {
        /*
         * Historical order items created before the
         * inventory-effect snapshot was introduced have
         * unknown stock provenance.
         *
         * Never guess whether stock should be restored.
         */
        if (
            $item->stock_was_decremented ===
            null
        ) {
            throw ValidationException::withMessages([
                'cancellation' => sprintf(
                    'Stock history is unavailable for order item "%s". Manual inventory review is required before cancellation.',
                    $item->product_name,
                ),
            ]);
        }

        /*
         * Checkout never touched inventory for this item.
         */
        if (
            $item->stock_was_decremented ===
            false
        ) {
            return;
        }

        /*
         * A tracked line needs its original inventory target
         * to still exist. Silently skipping it would lose
         * inventory and make cancellation inconsistent.
         */
        if ($item->product_id === null) {
            throw ValidationException::withMessages([
                'cancellation' => sprintf(
                    'The original product for "%s" no longer exists. Manual inventory review is required.',
                    $item->product_name,
                ),
            ]);
        }

        $product = Product::query()
            ->whereKey(
                $item->product_id,
            )
            ->lockForUpdate()
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'cancellation' => sprintf(
                    'The original product for "%s" no longer exists. Manual inventory review is required.',
                    $item->product_name,
                ),
            ]);
        }

        /*
         * The original order snapshot determines whether
         * inventory moved. Do NOT inspect the product's
         * current track_inventory value here.
         */
        if ($item->variant_id !== null) {
            $this->restoreVariant(
                item: $item,
                product: $product,
            );

            return;
        }

        $product->increment(
            'stock_quantity',
            $item->quantity,
        );
    }

    /**
     * @throws ValidationException
     */
    private function restoreVariant(
        OrderItem $item,
        Product $product,
    ): void {
        $variant = ProductVariant::query()
            ->where(
                'product_id',
                $product->id,
            )
            ->whereKey(
                $item->variant_id,
            )
            ->lockForUpdate()
            ->first();

        /*
         * Do not require is_active=true.
         *
         * A variant may have been disabled after purchase,
         * but stock deducted for the original order still
         * belongs back to that variant.
         */
        if ($variant === null) {
            throw ValidationException::withMessages([
                'cancellation' => sprintf(
                    'The original product variant for "%s" no longer exists. Manual inventory review is required.',
                    $item->product_name,
                ),
            ]);
        }

        $variant->increment(
            'stock_quantity',
            $item->quantity,
        );
    }
}
