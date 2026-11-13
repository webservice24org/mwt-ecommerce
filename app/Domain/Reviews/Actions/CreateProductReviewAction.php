<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Actions;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Domain\Reviews\Services\ProductReviewEligibilityService;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateProductReviewAction
{
    public function __construct(
        private ProductReviewEligibilityService $eligibility,
    ) {}

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
        array $data,
    ): ProductReview {
        return DB::transaction(
            function () use ($user, $product, $data): ProductReview {
                /*
                 * Serialize simultaneous review attempts
                 * from the same customer.
                 *
                 * The unique product/user database index
                 * remains the final integrity boundary.
                 */
                User::query()
                    ->whereKey(
                        $user->id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Eligibility is checked again inside the
                 * transaction.
                 */
                $eligibility =
                    $this->eligibility
                        ->check(
                            user: $user,

                            product: $product,
                        );

                if (
                    ! $eligibility
                        ->eligible
                ) {
                    throw ValidationException::withMessages([
                        'review' => $eligibility->reason ??
                            'You are not eligible to review this product.',
                    ]);
                }

                if (
                    $eligibility
                        ->verifiedOrderId ===
                    null
                ) {
                    throw ValidationException::withMessages([
                        'review' => 'The qualifying purchase could not be verified.',
                    ]);
                }

                return ProductReview::query()
                    ->create([
                        'product_id' => $product->id,

                        'user_id' => $user->id,

                        'verified_order_id' => $eligibility
                            ->verifiedOrderId,

                        'rating' => $data['rating'],

                        'title' => $data['title'] ??
                            null,

                        'body' => $data['body'],

                        'status' => ProductReviewStatus::Pending,

                        'is_verified_purchase' => true,

                        'moderated_by' => null,

                        'moderation_note' => null,

                        'moderated_at' => null,
                    ]);
            },
        );
    }
}
