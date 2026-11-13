<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Actions;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProductReviewAction
{
    /**
     * @param array{
     *     rating: int,
     *     title?: string|null,
     *     body: string
     * } $data
     */
    public function execute(
        User $user,
        Product $product,
        ProductReview $review,
        array $data,
    ): ProductReview {
        return DB::transaction(
            function () use ($user, $product, $review, $data): ProductReview {
                /*
                 * Resolve ownership inside the transaction.
                 *
                 * An arbitrary review ID must never permit
                 * editing another customer's review.
                 */
                $lockedReview =
                    ProductReview::query()
                        ->whereKey(
                            $review->id,
                        )
                        ->where(
                            'user_id',
                            $user->id,
                        )
                        ->where(
                            'product_id',
                            $product->id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedReview->update([
                    'rating' => $data['rating'],

                    'title' => $data['title'] ??
                        null,

                    'body' => $data['body'],

                    /*
                     * Any customer edit requires
                     * moderation again.
                     */
                    'status' => ProductReviewStatus::Pending,

                    'moderated_by' => null,

                    'moderation_note' => null,

                    'moderated_at' => null,
                ]);

                /*
                 * verified_order_id and
                 * is_verified_purchase deliberately
                 * remain unchanged.
                 */
                return $lockedReview
                    ->refresh();
            },
        );
    }
}
