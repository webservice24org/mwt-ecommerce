<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
final class ProductReviewFactory extends Factory
{
    protected $model =
        ProductReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),

            'user_id' => User::factory(),

            'verified_order_id' => null,

            'moderated_by' => null,

            'rating' => fake()->numberBetween(
                1,
                5,
            ),

            'title' => fake()->optional()
                ->sentence(5),

            'body' => fake()->paragraphs(
                2,
                true,
            ),

            'status' => ProductReviewStatus::Pending,

            'is_verified_purchase' => false,

            'moderation_note' => null,

            'moderated_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ProductReviewStatus::Approved,

                'moderated_at' => now(),
            ],
        );
    }

    public function rejected(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ProductReviewStatus::Rejected,

                'moderated_at' => now(),
            ],
        );
    }

    public function verifiedPurchase(): static
    {
        return $this->state(
            fn (): array => [
                'is_verified_purchase' => true,
            ],
        );
    }
}
