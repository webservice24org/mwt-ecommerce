<?php

declare(strict_types=1);

namespace Tests\Feature\Reviews;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Domain\Reviews\Queries\StorefrontProductReviewsQuery;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductReviewSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_submit_review(): void
    {
        $product =
            Product::factory()
                ->create();

        $this
            ->post(
                route(
                    'products.reviews.store',
                    [
                        'product' => $product->id,
                    ],
                ),
                [
                    'rating' => 5,

                    'title' => 'Guest review',

                    'body' => 'Guests must never be allowed to submit product reviews.',
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_customer_without_completed_delivered_purchase_cannot_review(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'fulfilment_status' => FulfilmentStatus::Shipped,
                ]);

        $this->attachProduct(
            order: $order,

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

                    'title' => 'Too early',

                    'body' => 'The order has not reached completed and delivered state.',
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

    public function test_customer_cannot_use_another_customers_qualifying_order(): void
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
            $this->qualifyingOrder(
                customer: $otherCustomer,
            );

        $this->attachProduct(
            order: $order,

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

                    'title' => 'Invalid ownership',

                    'body' => 'Another customer purchase must never qualify this account.',
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

    public function test_customer_cannot_force_verified_purchase_fields(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->qualifyingOrder(
                customer: $customer,
            );

        $this->attachProduct(
            order: $order,

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

                    'title' => 'Tampered',

                    'body' => 'Server owned verification fields must not be accepted from the client.',

                    'verified_order_id' => $order->id,

                    'is_verified_purchase' => false,

                    'status' => ProductReviewStatus::Approved
                        ->value,
                ],
            )
            ->assertSessionHasErrors([
                'verified_order_id',
                'is_verified_purchase',
                'status',
            ]);

        $this->assertDatabaseCount(
            'product_reviews',
            0,
        );
    }

    public function test_server_chooses_verified_order_id(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $olderOrder =
            $this->qualifyingOrder(
                customer: $customer,

                placedAt: '2026-10-01 10:00:00',
            );

        $newerOrder =
            $this->qualifyingOrder(
                customer: $customer,

                placedAt: '2026-11-01 10:00:00',
            );

        $this->attachProduct(
            order: $olderOrder,

            product: $product,
        );

        $this->attachProduct(
            order: $newerOrder,

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

                    'title' => 'Verified',

                    'body' => 'The server should choose the latest qualifying completed delivery.',
                ],
            )
            ->assertSessionHasNoErrors();

        $review =
            ProductReview::query()
                ->firstOrFail();

        $this->assertSame(
            $newerOrder->id,
            $review
                ->verified_order_id,
        );

        $this->assertTrue(
            $review
                ->is_verified_purchase,
        );
    }

    public function test_duplicate_review_for_same_customer_product_is_blocked(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->qualifyingOrder(
                customer: $customer,
            );

        $this->attachProduct(
            order: $order,

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

                    'body' => 'A second review for the same customer and product must fail.',
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

    public function test_customer_cannot_edit_another_customers_review(): void
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

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $owner->id,

                    'rating' => 4,

                    'title' => 'Owner review',

                    'body' => 'Only the customer who owns this review may edit it.',
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

                    'title' => 'Attacker edit',

                    'body' => 'This edit must not be allowed.',
                ],
            )
            ->assertNotFound();

        $review->refresh();

        $this->assertSame(
            $owner->id,
            $review
                ->user_id,
        );

        $this->assertSame(
            4,
            $review
                ->rating,
        );

        $this->assertSame(
            'Owner review',
            $review
                ->title,
        );
    }

    public function test_customer_cannot_edit_review_through_wrong_product_route(): void
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

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'rating' => 5,

                    'body' => 'This review belongs to the original product only.',
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

                    'title' => 'Wrong route',

                    'body' => 'The review must not be movable between products.',
                ],
            )
            ->assertNotFound();

        $review->refresh();

        $this->assertSame(
            $product->id,
            $review
                ->product_id,
        );

        $this->assertSame(
            5,
            $review
                ->rating,
        );
    }

    public function test_editing_approved_review_resets_moderation_and_hides_it_publicly(): void
    {
        $customer =
            User::factory()
                ->create();

        $admin =
            $this->createAdmin();

        $product =
            Product::factory()
                ->create();

        $review =
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'rating' => 5,

                    'is_verified_purchase' => true,

                    'moderated_by' => $admin->id,

                    'moderation_note' => 'Approved.',

                    'moderated_at' => now(),
                ]);

        $before =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            1,
            $before['summary']['review_count'],
        );

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

                    'title' => 'Edited review',

                    'body' => 'The customer changed the review and it must be moderated again.',
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

        $after =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            0,
            $after['summary']['review_count'],
        );

        $this->assertSame(
            [],
            $after['reviews']['data'],
        );
    }

    public function test_pending_and_rejected_reviews_never_affect_public_aggregation(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,
            ]);

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,

                'status' => ProductReviewStatus::Pending,
            ]);

        ProductReview::factory()
            ->rejected()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            1,
            $result['summary']['review_count'],
        );

        $this->assertSame(
            5.0,
            $result['summary']['average_rating'],
        );

        $distribution =
            collect(
                $result['summary']['distribution'],
            )
                ->keyBy(
                    'rating',
                );

        $this->assertSame(
            1,
            $distribution[5]['count'],
        );

        $this->assertSame(
            0,
            $distribution[1]['count'],
        );
    }

    public function test_approving_review_changes_public_aggregation_immediately(): void
    {
        $admin =
            $this->createAdmin();

        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,
            ]);

        $pending =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'rating' => 1,

                    'status' => ProductReviewStatus::Pending,
                ]);

        $before =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            1,
            $before['summary']['review_count'],
        );

        $this->assertSame(
            5.0,
            $before['summary']['average_rating'],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.reviews.approve',
                    $pending,
                ),
            )
            ->assertSessionHasNoErrors();

        $after =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            2,
            $after['summary']['review_count'],
        );

        $this->assertSame(
            3.0,
            $after['summary']['average_rating'],
        );
    }

    public function test_rejecting_approved_review_removes_it_from_public_aggregation_immediately(): void
    {
        $admin =
            $this->createAdmin();

        $product =
            Product::factory()
                ->create();

        $review =
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'rating' => 2,
                ]);

        $before =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            1,
            $before['summary']['review_count'],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.reviews.reject',
                    $review,
                ),
            )
            ->assertSessionHasNoErrors();

        $after =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            0,
            $after['summary']['review_count'],
        );

        $this->assertSame(
            0.0,
            $after['summary']['average_rating'],
        );
    }

    public function test_public_payload_never_exposes_private_customer_or_moderation_data(): void
    {
        $admin =
            $this->createAdmin();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Public Reviewer',

                    'email' => 'private-customer@example.com',
                ]);

        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'user_id' => $customer->id,

                'rating' => 5,

                'title' => 'Public title',

                'body' => 'Public review body.',

                'moderated_by' => $admin->id,

                'moderation_note' => 'PRIVATE MODERATOR NOTE',

                'is_verified_purchase' => true,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $encoded =
            json_encode(
                $result,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringContainsString(
            'Public Reviewer',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'private-customer@example.com',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE MODERATOR NOTE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'moderated_by',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'moderation_note',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'verified_order_id',
            $encoded,
        );
    }

    public function test_admin_moderation_cannot_change_customer_owned_review_content(): void
    {
        $admin =
            $this->createAdmin();

        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'user_id' => $customer->id,

                    'rating' => 4,

                    'title' => 'Original title',

                    'body' => 'Original review body.',
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.reviews.approve',
                    $review,
                ),
                [
                    'note' => 'Approved.',

                    'rating' => 1,

                    'title' => 'Injected title',

                    'body' => 'Injected body',

                    'product_id' => Product::factory()
                        ->create()
                        ->id,

                    'user_id' => User::factory()
                        ->create()
                        ->id,
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            4,
            $review
                ->rating,
        );

        $this->assertSame(
            'Original title',
            $review
                ->title,
        );

        $this->assertSame(
            'Original review body.',
            $review
                ->body,
        );

        $this->assertSame(
            $product->id,
            $review
                ->product_id,
        );

        $this->assertSame(
            $customer->id,
            $review
                ->user_id,
        );

        $this->assertSame(
            ProductReviewStatus::Approved,
            $review
                ->status,
        );
    }

    public function test_customer_web_session_cannot_access_admin_review_routes(): void
    {
        $customer =
            User::factory()
                ->create();

        $review =
            ProductReview::factory()
                ->create();

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'admin.reviews.index',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'admin.reviews.approve',
                    $review,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Pending,
            $review
                ->status,
        );
    }

    public function test_distribution_counts_equal_public_review_count(): void
    {
        $product =
            Product::factory()
                ->create();

        foreach (
            [
                5,
                5,
                4,
                3,
                3,
                1,
            ] as $rating
        ) {
            ProductReview::factory()
                ->approved()
                ->create([
                    'product_id' => $product->id,

                    'rating' => $rating,
                ]);
        }

        ProductReview::factory()
            ->count(3)
            ->create([
                'product_id' => $product->id,

                'status' => ProductReviewStatus::Pending,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $distributionCount =
            collect(
                $result['summary']['distribution'],
            )
                ->sum(
                    'count',
                );

        $this->assertSame(
            $result['summary']['review_count'],
            $distributionCount,
        );
    }

    public function test_distribution_percentages_are_consistent_with_public_count(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductReview::factory()
            ->count(3)
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 5,
            ]);

        ProductReview::factory()
            ->approved()
            ->create([
                'product_id' => $product->id,

                'rating' => 1,
            ]);

        $result =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $distribution =
            collect(
                $result['summary']['distribution'],
            )
                ->keyBy(
                    'rating',
                );

        $this->assertSame(
            75.0,
            $distribution[5]['percentage'],
        );

        $this->assertSame(
            25.0,
            $distribution[1]['percentage'],
        );

        $this->assertSame(
            100.0,
            round(
                collect(
                    $result['summary']['distribution'],
                )->sum(
                    'percentage',
                ),
                1,
            ),
        );
    }

    private function qualifyingOrder(
        User $customer,
        string $placedAt = '2026-11-10 12:00:00',
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => OrderStatus::Completed,

                'fulfilment_status' => FulfilmentStatus::Delivered,

                'placed_at' => $placedAt,
            ]);
    }

    private function attachProduct(
        Order $order,
        Product $product,
    ): OrderItem {
        return OrderItem::factory()
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
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()
            ->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);
    }
}
