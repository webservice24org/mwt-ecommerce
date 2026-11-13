<?php

declare(strict_types=1);

namespace Tests\Feature\Reviews;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_customer_can_submit_verified_product_review(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        $response =
            $this
                ->actingAs(
                    $customer,
                )
                ->from(
                    route(
                        'products.show',
                        $product->slug,
                    ),
                )
                ->post(
                    route(
                        'products.reviews.store',
                        [
                            'product' => $product->id,
                        ],
                    ),
                    [
                        'rating' => 5,

                        'title' => 'Excellent product',

                        'body' => 'The product quality was excellent and it arrived exactly as expected.',
                    ],
                );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'products.show',
                    $product->slug,
                ),
            );

        $review =
            ProductReview::query()
                ->where(
                    'product_id',
                    $product->id,
                )
                ->where(
                    'user_id',
                    $customer->id,
                )
                ->firstOrFail();

        $this->assertSame(
            5,
            $review->rating,
        );

        $this->assertSame(
            'Excellent product',
            $review->title,
        );

        $this->assertSame(
            'The product quality was excellent and it arrived exactly as expected.',
            $review->body,
        );

        $this->assertSame(
            ProductReviewStatus::Pending,
            $review->status,
        );

        $this->assertTrue(
            $review
                ->is_verified_purchase,
        );

        $this->assertSame(
            $order->id,
            $review
                ->verified_order_id,
        );

        $this->assertNull(
            $review
                ->moderated_by,
        );

        $this->assertNull(
            $review
                ->moderation_note,
        );

        $this->assertNull(
            $review
                ->moderated_at,
        );
    }

    public function test_review_title_is_optional(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $this->createQualifyingPurchase(
            customer: $customer,

            product: $product,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 4,

                    'title' => '',

                    'body' => 'This product works very well and I am satisfied with my purchase.',
                ],
            )
            ->assertSessionHasNoErrors();

        $review =
            ProductReview::query()
                ->firstOrFail();

        $this->assertNull(
            $review->title,
        );
    }

    public function test_rating_below_one_is_rejected(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $this->createQualifyingPurchase(
            customer: $customer,

            product: $product,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 0,

                    'title' => 'Invalid rating',

                    'body' => 'This review body is long enough to pass the body validation.',
                ],
            )
            ->assertSessionHasErrors(
                'rating',
            );

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_rating_above_five_is_rejected(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $this->createQualifyingPurchase(
            customer: $customer,

            product: $product,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 6,

                    'title' => 'Invalid rating',

                    'body' => 'This review body is long enough to pass the body validation.',
                ],
            )
            ->assertSessionHasErrors(
                'rating',
            );

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_review_body_must_meet_minimum_length(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $this->createQualifyingPurchase(
            customer: $customer,

            product: $product,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Short',

                    'body' => 'Too short',
                ],
            )
            ->assertSessionHasErrors(
                'body',
            );

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_customer_without_qualifying_purchase_cannot_submit_review(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Should not work',

                    'body' => 'This customer never purchased and completed delivery of this product.',
                ],
            )
            ->assertSessionHasErrors(
                'review',
            );

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_another_customers_purchase_does_not_make_customer_eligible(): void
    {
        $customer =
            User::factory()
                ->create();

        $otherCustomer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $this->createQualifyingPurchase(
            customer: $otherCustomer,

            product: $product,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Unauthorized review',

                    'body' => 'The purchase belongs to another customer and must not qualify this customer.',
                ],
            )
            ->assertSessionHasErrors(
                'review',
            );

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_server_owned_review_fields_are_prohibited(): void
    {
        $customer =
            User::factory()
                ->create();

        $otherCustomer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Attempted tampering',

                    'body' => 'This payload attempts to change server-owned review fields and must fail.',

                    'product_id' => $product->id,

                    'user_id' => $otherCustomer->id,

                    'verified_order_id' => $order->id,

                    'is_verified_purchase' => true,

                    'status' => ProductReviewStatus::Approved
                        ->value,

                    'moderated_by' => $otherCustomer->id,

                    'moderation_note' => 'Client controlled',

                    'moderated_at' => now()->toDateTimeString(),
                ],
            )
            ->assertSessionHasErrors([
                'product_id',
                'user_id',
                'verified_order_id',
                'is_verified_purchase',
                'status',
                'moderated_by',
                'moderation_note',
                'moderated_at',
            ]);

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_existing_review_prevents_duplicate_submission(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'user_id' => $customer->id,

                'verified_order_id' => $order->id,

                'is_verified_purchase' => true,
            ]);

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 4,

                    'title' => 'Duplicate',

                    'body' => 'This second review must not be created for the same customer and product.',
                ],
            )
            ->assertSessionHasErrors(
                'review',
            );

        $this->assertDatabaseCount(
            'product_reviews',
            1,
        );
    }

    public function test_customer_can_update_own_review(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'verified_order_id' => $order->id,

                    'rating' => 3,

                    'title' => 'Original review',

                    'body' => 'This is the original review content before the customer updates it.',

                    'status' => ProductReviewStatus::Pending,

                    'is_verified_purchase' => true,
                ]);

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                route(
                    'products.reviews.update',
                    [
                        'product' => $product->id,

                        'review' => $review->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Updated review',

                    'body' => 'This is the updated review content after using the product for longer.',
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            5,
            $review->rating,
        );

        $this->assertSame(
            'Updated review',
            $review->title,
        );

        $this->assertSame(
            'This is the updated review content after using the product for longer.',
            $review->body,
        );

        $this->assertSame(
            ProductReviewStatus::Pending,
            $review->status,
        );

        /*
         * Verified-purchase provenance must survive edits.
         */
        $this->assertSame(
            $order->id,
            $review
                ->verified_order_id,
        );

        $this->assertTrue(
            $review
                ->is_verified_purchase,
        );
    }

    public function test_editing_approved_review_returns_it_to_pending_moderation(): void
    {
        $customer =
            User::factory()
                ->create();

        $moderator =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        $review =
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'verified_order_id' => $order->id,

                    'rating' => 4,

                    'title' => 'Previously approved',

                    'body' => 'This review was approved before the customer decided to edit the content.',

                    'is_verified_purchase' => true,

                    'moderated_by' => $moderator->id,

                    'moderation_note' => 'Approved by moderator.',

                    'moderated_at' => now(),
                ]);

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                route(
                    'products.reviews.update',
                    [
                        'product' => $product->id,

                        'review' => $review->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Changed after approval',

                    'body' => 'The customer changed the approved review so moderation must run again.',
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Pending,
            $review->status,
        );

        $this->assertNull(
            $review
                ->moderated_by,
        );

        $this->assertNull(
            $review
                ->moderation_note,
        );

        $this->assertNull(
            $review
                ->moderated_at,
        );

        /*
         * Purchase provenance is immutable during
         * customer edits.
         */
        $this->assertSame(
            $order->id,
            $review
                ->verified_order_id,
        );

        $this->assertTrue(
            $review
                ->is_verified_purchase,
        );
    }

    public function test_editing_rejected_review_returns_it_to_pending_moderation(): void
    {
        $customer =
            User::factory()
                ->create();

        $moderator =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        $review =
            ProductReview::factory()
                ->rejected()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'verified_order_id' => $order->id,

                    'is_verified_purchase' => true,

                    'moderated_by' => $moderator->id,

                    'moderation_note' => 'Please improve the review.',

                    'moderated_at' => now(),
                ]);

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                route(
                    'products.reviews.update',
                    [
                        'product' => $product->id,

                        'review' => $review->id,
                    ],
                ),
                [
                    'rating' => 4,

                    'title' => 'Improved review',

                    'body' => 'I updated the review with clearer and more useful information about the product.',
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Pending,
            $review->status,
        );

        $this->assertNull(
            $review
                ->moderated_by,
        );

        $this->assertNull(
            $review
                ->moderation_note,
        );

        $this->assertNull(
            $review
                ->moderated_at,
        );

        $this->assertSame(
            $order->id,
            $review
                ->verified_order_id,
        );
    }

    public function test_customer_cannot_update_another_customers_review(): void
    {
        $owner =
            User::factory()
                ->create();

        $attacker =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $owner,

                product: $product,
            );

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $owner->id,

                    'verified_order_id' => $order->id,

                    'is_verified_purchase' => true,

                    'rating' => 4,

                    'title' => 'Owner review',

                    'body' => 'This review belongs to the original customer and cannot be edited by another user.',
                ]);

        $this
            ->actingAs(
                $attacker,
            )
            ->patch(
                route(
                    'products.reviews.update',
                    [
                        'product' => $product->id,

                        'review' => $review->id,
                    ],
                ),
                [
                    'rating' => 1,

                    'title' => 'Tampered',

                    'body' => 'Another customer must never be allowed to modify this review content.',
                ],
            )
            ->assertNotFound();

        $review->refresh();

        $this->assertSame(
            4,
            $review->rating,
        );

        $this->assertSame(
            'Owner review',
            $review->title,
        );
    }

    public function test_review_cannot_be_updated_through_different_product(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $otherProduct =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'verified_order_id' => $order->id,

                    'is_verified_purchase' => true,

                    'rating' => 5,

                    'body' => 'This review belongs only to its original product and route combination.',
                ]);

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                route(
                    'products.reviews.update',
                    [
                        'product' => $otherProduct->id,

                        'review' => $review->id,
                    ],
                ),
                [
                    'rating' => 1,

                    'title' => 'Wrong product',

                    'body' => 'The route product does not match the review product and must be rejected.',
                ],
            )
            ->assertNotFound();

        $review->refresh();

        $this->assertSame(
            $product->id,
            $review->product_id,
        );

        $this->assertSame(
            5,
            $review->rating,
        );
    }

    public function test_server_owned_fields_cannot_be_changed_during_review_update(): void
    {
        $customer =
            User::factory()
                ->create();

        $otherCustomer =
            User::factory()
                ->create();

        $moderator =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createQualifyingPurchase(
                customer: $customer,

                product: $product,
            );

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'verified_order_id' => $order->id,

                    'is_verified_purchase' => true,

                    'rating' => 4,

                    'body' => 'Original review content that should remain unchanged after invalid update.',
                ]);

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                route(
                    'products.reviews.update',
                    [
                        'product' => $product->id,

                        'review' => $review->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Attempted update',

                    'body' => 'This request contains otherwise valid editable review fields.',

                    'user_id' => $otherCustomer->id,

                    'product_id' => $product->id,

                    'verified_order_id' => $order->id,

                    'is_verified_purchase' => false,

                    'status' => ProductReviewStatus::Approved
                        ->value,

                    'moderated_by' => $moderator->id,

                    'moderation_note' => 'Injected moderation note',

                    'moderated_at' => now()->toDateTimeString(),
                ],
            )
            ->assertSessionHasErrors([
                'user_id',
                'product_id',
                'verified_order_id',
                'is_verified_purchase',
                'status',
                'moderated_by',
                'moderation_note',
                'moderated_at',
            ]);

        $review->refresh();

        $this->assertSame(
            4,
            $review->rating,
        );

        $this->assertSame(
            $customer->id,
            $review->user_id,
        );

        $this->assertSame(
            $order->id,
            $review
                ->verified_order_id,
        );

        $this->assertTrue(
            $review
                ->is_verified_purchase,
        );
    }

    public function test_guest_cannot_submit_product_review(): void
    {
        $product =
            Product::factory()
                ->create();

        $response =
            $this->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Guest review',

                    'body' => 'A guest user must not be able to submit a product review.',
                ],
            );

        $response->assertRedirect();

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    private function createQualifyingPurchase(
        User $customer,
        Product $product,
        string $placedAt = '2026-11-10 12:00:00',
    ): Order {
        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Completed,

                    'fulfilment_status' => FulfilmentStatus::Delivered,

                    'placed_at' => $placedAt,
                ]);

        OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => $product->name,

                'sku' => $product->sku,

                'unit_price' => 10000,

                'quantity' => 1,

                'line_total' => 10000,
            ]);

        return $order;
    }
}
