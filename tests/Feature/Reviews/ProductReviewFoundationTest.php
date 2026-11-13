<?php

declare(strict_types=1);

namespace Tests\Feature\Reviews;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductReviewFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_defaults_to_pending(): void
    {
        $review =
            ProductReview::factory()
                ->create();

        $this->assertSame(
            ProductReviewStatus::Pending,
            $review->status,
        );

        $this->assertFalse(
            $review
                ->is_verified_purchase,
        );

        $this->assertNull(
            $review->moderated_at,
        );
    }

    public function test_review_casts_domain_values(): void
    {
        $review =
            ProductReview::factory()
                ->approved()
                ->verifiedPurchase()
                ->create([
                    'rating' => 5,
                ]);

        $this->assertSame(
            5,
            $review->rating,
        );

        $this->assertSame(
            ProductReviewStatus::Approved,
            $review->status,
        );

        $this->assertTrue(
            $review
                ->is_verified_purchase,
        );

        $this->assertNotNull(
            $review->moderated_at,
        );
    }

    public function test_review_belongs_to_product_and_customer(): void
    {
        $product =
            Product::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,
                ]);

        $this->assertTrue(
            $review
                ->product
                ->is(
                    $product,
                ),
        );

        $this->assertTrue(
            $review
                ->user
                ->is(
                    $customer,
                ),
        );
    }

    public function test_product_has_reviews_relationship(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->count(3)
            ->for(
                $product,
            )
            ->create();

        $this->assertCount(
            3,
            $product
                ->reviews()
                ->get(),
        );
    }

    public function test_customer_has_product_reviews_relationship(): void
    {
        $customer =
            User::factory()
                ->create();

        ProductReview::factory()
            ->count(2)
            ->for(
                $customer,
                'user',
            )
            ->create();

        $this->assertCount(
            2,
            $customer
                ->productReviews()
                ->get(),
        );
    }

    public function test_customer_cannot_have_duplicate_review_for_same_product(): void
    {
        $product =
            Product::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'user_id' => $customer->id,
            ]);

        $this->expectException(
            QueryException::class,
        );

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'user_id' => $customer->id,
            ]);
    }

    public function test_same_customer_can_review_different_products(): void
    {
        $customer =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->create([
                'product_id' => $firstProduct->id,

                'user_id' => $customer->id,
            ]);

        ProductReview::factory()
            ->create([
                'product_id' => $secondProduct->id,

                'user_id' => $customer->id,
            ]);

        $this->assertDatabaseCount(
            'product_reviews',
            2,
        );
    }

    public function test_review_status_helpers_follow_enum_state(): void
    {
        $pending =
            ProductReview::factory()
                ->make([
                    'status' => ProductReviewStatus::Pending,
                ]);

        $approved =
            ProductReview::factory()
                ->make([
                    'status' => ProductReviewStatus::Approved,
                ]);

        $rejected =
            ProductReview::factory()
                ->make([
                    'status' => ProductReviewStatus::Rejected,
                ]);

        $this->assertTrue(
            $pending->isPending(),
        );

        $this->assertTrue(
            $approved->isApproved(),
        );

        $this->assertTrue(
            $rejected->isRejected(),
        );

        $this->assertFalse(
            $pending->isApproved(),
        );
    }
}
