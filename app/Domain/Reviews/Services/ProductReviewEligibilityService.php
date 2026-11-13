<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Services;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reviews\Data\ProductReviewEligibilityData;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final readonly class ProductReviewEligibilityService
{
    public function check(
        User $user,
        Product $product,
    ): ProductReviewEligibilityData {
        /*
         * ==================================================
         * ONE REVIEW PER CUSTOMER / PRODUCT
         * ==================================================
         *
         * The database unique index remains the final
         * concurrency boundary.
         *
         * This early check gives the application a clean
         * eligibility result before attempting insertion.
         */
        $alreadyReviewed =
            ProductReview::query()
                ->where(
                    'product_id',
                    $product->id,
                )
                ->where(
                    'user_id',
                    $user->id,
                )
                ->exists();

        if ($alreadyReviewed) {
            return ProductReviewEligibilityData::alreadyReviewed();
        }

        /*
         * ==================================================
         * VERIFIED PURCHASE LOOKUP
         * ==================================================
         *
         * A review qualifies only when:
         *
         * - the order belongs to this customer
         * - the order itself is Completed
         * - fulfilment is Delivered
         * - an immutable order-item snapshot references
         *   this product
         *
         * Client-provided order IDs are never trusted.
         */
        $verifiedOrder =
            Order::query()
                ->select([
                    'id',
                    'placed_at',
                ])
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'status',
                    OrderStatus::Completed
                        ->value,
                )
                ->where(
                    'fulfilment_status',
                    FulfilmentStatus::Delivered
                        ->value,
                )
                ->whereHas(
                    'items',
                    static function (Builder $query) use ($product): void {
                        $query->where(
                            'product_id',
                            $product->id,
                        );
                    },
                )
                ->orderByDesc(
                    'placed_at',
                )
                ->orderByDesc(
                    'id',
                )
                ->first();

        if (
            ! $verifiedOrder instanceof Order
        ) {
            return ProductReviewEligibilityData::noVerifiedPurchase();
        }

        return ProductReviewEligibilityData::eligible(
            verifiedOrderId: $verifiedOrder->id,
        );
    }
}
