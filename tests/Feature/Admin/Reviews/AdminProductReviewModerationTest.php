<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Reviews;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Domain\Reviews\Queries\StorefrontProductReviewsQuery;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminProductReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_view_product_reviews_index(): void
    {
        $admin =
            $this->createAdmin();

        ProductReview::factory()
            ->count(3)
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Reviews/Index',
                    )
                    ->has(
                        'reviews.data',
                        3,
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'statusOptions',
                        3,
                    ),
            );
    }

    public function test_guest_cannot_access_product_review_admin_index(): void
    {
        $this
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
    }

    public function test_guest_cannot_approve_product_review(): void
    {
        $review =
            ProductReview::factory()
                ->create();

        $this
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
            $review->status,
        );

        $this->assertNull(
            $review
                ->moderated_by,
        );
    }

    public function test_guest_cannot_reject_product_review(): void
    {
        $review =
            ProductReview::factory()
                ->create();

        $this
            ->post(
                route(
                    'admin.reviews.reject',
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
            $review->status,
        );

        $this->assertNull(
            $review
                ->moderated_by,
        );
    }

    public function test_admin_can_approve_pending_review(): void
    {
        $admin =
            $this->createAdmin();

        $review =
            ProductReview::factory()
                ->create([
                    'status' => ProductReviewStatus::Pending,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.reviews.index',
                ),
            )
            ->post(
                route(
                    'admin.reviews.approve',
                    $review,
                ),
                [
                    'note' => 'Reviewed and approved.',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.reviews.index',
                ),
            );

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Approved,
            $review->status,
        );

        $this->assertSame(
            $admin->id,
            $review
                ->moderated_by,
        );

        $this->assertSame(
            'Reviewed and approved.',
            $review
                ->moderation_note,
        );

        $this->assertNotNull(
            $review
                ->moderated_at,
        );
    }

    public function test_admin_can_reject_pending_review(): void
    {
        $admin =
            $this->createAdmin();

        $review =
            ProductReview::factory()
                ->create([
                    'status' => ProductReviewStatus::Pending,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.reviews.index',
                ),
            )
            ->post(
                route(
                    'admin.reviews.reject',
                    $review,
                ),
                [
                    'note' => 'Review does not meet moderation guidelines.',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.reviews.index',
                ),
            );

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Rejected,
            $review->status,
        );

        $this->assertSame(
            $admin->id,
            $review
                ->moderated_by,
        );

        $this->assertSame(
            'Review does not meet moderation guidelines.',
            $review
                ->moderation_note,
        );

        $this->assertNotNull(
            $review
                ->moderated_at,
        );
    }

    public function test_moderation_note_is_optional(): void
    {
        $admin =
            $this->createAdmin();

        $review =
            ProductReview::factory()
                ->create();

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
                    'note' => '',
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Approved,
            $review->status,
        );

        $this->assertNull(
            $review
                ->moderation_note,
        );
    }

    public function test_moderation_note_is_trimmed(): void
    {
        $admin =
            $this->createAdmin();

        $review =
            ProductReview::factory()
                ->create();

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
                [
                    'note' => '   Needs more useful product information.   ',
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            'Needs more useful product information.',
            $review
                ->moderation_note,
        );
    }

    public function test_moderation_note_cannot_exceed_maximum_length(): void
    {
        $admin =
            $this->createAdmin();

        $review =
            ProductReview::factory()
                ->create();

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
                    'note' => str_repeat(
                        'A',
                        2001,
                    ),
                ],
            )
            ->assertSessionHasErrors(
                'note',
            );

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Pending,
            $review->status,
        );

        $this->assertNull(
            $review
                ->moderated_by,
        );
    }

    public function test_second_moderator_replaces_last_moderation_audit(): void
    {
        $firstAdmin =
            $this->createAdmin();

        $secondAdmin =
            $this->createAdmin();

        $review =
            ProductReview::factory()
                ->create();

        $this
            ->actingAs(
                $firstAdmin,
                'admin',
            )
            ->post(
                route(
                    'admin.reviews.approve',
                    $review,
                ),
                [
                    'note' => 'Initially approved.',
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $firstModeratedAt =
            $review
                ->moderated_at;

        $this
            ->actingAs(
                $secondAdmin,
                'admin',
            )
            ->post(
                route(
                    'admin.reviews.reject',
                    $review,
                ),
                [
                    'note' => 'Reviewed again and rejected.',
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            ProductReviewStatus::Rejected,
            $review->status,
        );

        $this->assertSame(
            $secondAdmin->id,
            $review
                ->moderated_by,
        );

        $this->assertSame(
            'Reviewed again and rejected.',
            $review
                ->moderation_note,
        );

        $this->assertNotNull(
            $review
                ->moderated_at,
        );

        $this->assertNotNull(
            $firstModeratedAt,
        );
    }

    public function test_moderation_does_not_modify_customer_review_content_or_purchase_provenance(): void
    {
        $admin =
            $this->createAdmin();

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

                    'rating' => 4,

                    'title' => 'Original customer title',

                    'body' => 'Original customer review body that must remain unchanged.',

                    'is_verified_purchase' => true,
                ]);

        $originalVerifiedOrderId =
            $review
                ->verified_order_id;

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
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

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
            4,
            $review
                ->rating,
        );

        $this->assertSame(
            'Original customer title',
            $review
                ->title,
        );

        $this->assertSame(
            'Original customer review body that must remain unchanged.',
            $review
                ->body,
        );

        $this->assertSame(
            $originalVerifiedOrderId,
            $review
                ->verified_order_id,
        );

        $this->assertTrue(
            $review
                ->is_verified_purchase,
        );
    }

    public function test_extra_customer_owned_fields_in_moderation_request_are_ignored(): void
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

                    'rating' => 5,

                    'title' => 'Untouched title',

                    'body' => 'This body must not be changed by moderation input.',
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

                    'is_verified_purchase' => false,
                ],
            )
            ->assertSessionHasNoErrors();

        $review->refresh();

        $this->assertSame(
            5,
            $review
                ->rating,
        );

        $this->assertSame(
            'Untouched title',
            $review
                ->title,
        );

        $this->assertSame(
            'This body must not be changed by moderation input.',
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
    }

    public function test_admin_index_can_search_review_title(): void
    {
        $admin =
            $this->createAdmin();

        $target =
            ProductReview::factory()
                ->create([
                    'title' => 'Unique Solar Charger Review',
                ]);

        ProductReview::factory()
            ->create([
                'title' => 'Completely Different Review',
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'search' => 'Solar Charger',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $target->id,
                    ),
            );
    }

    public function test_admin_index_can_search_review_body(): void
    {
        $admin =
            $this->createAdmin();

        $target =
            ProductReview::factory()
                ->create([
                    'body' => 'The battery performance was exceptional during travel.',
                ]);

        ProductReview::factory()
            ->create([
                'body' => 'This review contains unrelated text.',
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'search' => 'exceptional during travel',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $target->id,
                    ),
            );
    }

    public function test_admin_index_can_search_product_name(): void
    {
        $admin =
            $this->createAdmin();

        $product =
            Product::factory()
                ->create([
                    'name' => 'MWT Premium Keyboard',
                ]);

        $target =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,
                ]);

        ProductReview::factory()
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'search' => 'Premium Keyboard',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $target->id,
                    ),
            );
    }

    public function test_admin_index_can_search_product_sku(): void
    {
        $admin =
            $this->createAdmin();

        $product =
            Product::factory()
                ->create([
                    'sku' => 'REVIEW-SKU-98765',
                ]);

        $target =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,
                ]);

        ProductReview::factory()
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'search' => 'REVIEW-SKU-98765',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $target->id,
                    ),
            );
    }

    public function test_admin_index_can_search_customer_name(): void
    {
        $admin =
            $this->createAdmin();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Unique Review Customer',
                ]);

        $target =
            ProductReview::factory()
                ->create([
                    'user_id' => $customer->id,
                ]);

        ProductReview::factory()
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'search' => 'Unique Review Customer',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $target->id,
                    ),
            );
    }

    public function test_admin_index_can_search_customer_email(): void
    {
        $admin =
            $this->createAdmin();

        $customer =
            User::factory()
                ->create([
                    'email' => 'review-search@example.com',
                ]);

        $target =
            ProductReview::factory()
                ->create([
                    'user_id' => $customer->id,
                ]);

        ProductReview::factory()
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'search' => 'review-search@example.com',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $target->id,
                    ),
            );
    }

    public function test_admin_index_can_filter_pending_reviews(): void
    {
        $admin =
            $this->createAdmin();

        $pending =
            ProductReview::factory()
                ->create([
                    'status' => ProductReviewStatus::Pending,
                ]);

        ProductReview::factory()
            ->approved()
            ->create();

        ProductReview::factory()
            ->rejected()
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'status' => ProductReviewStatus::Pending
                            ->value,
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $pending->id,
                    ),
            );
    }

    public function test_admin_index_can_filter_approved_reviews(): void
    {
        $admin =
            $this->createAdmin();

        ProductReview::factory()
            ->create();

        $approved =
            ProductReview::factory()
                ->approved()
                ->create();

        ProductReview::factory()
            ->rejected()
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'status' => ProductReviewStatus::Approved
                            ->value,
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $approved->id,
                    ),
            );
    }

    public function test_admin_index_can_filter_rejected_reviews(): void
    {
        $admin =
            $this->createAdmin();

        ProductReview::factory()
            ->create();

        ProductReview::factory()
            ->approved()
            ->create();

        $rejected =
            ProductReview::factory()
                ->rejected()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'status' => ProductReviewStatus::Rejected
                            ->value,
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $rejected->id,
                    ),
            );
    }

    public function test_admin_index_can_filter_by_rating(): void
    {
        $admin =
            $this->createAdmin();

        ProductReview::factory()
            ->create([
                'rating' => 3,
            ]);

        $fiveStar =
            ProductReview::factory()
                ->create([
                    'rating' => 5,
                ]);

        ProductReview::factory()
            ->create([
                'rating' => 1,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'rating' => 5,
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $fiveStar->id,
                    ),
            );
    }

    public function test_admin_index_can_filter_verified_purchase_reviews(): void
    {
        $admin =
            $this->createAdmin();

        $verified =
            ProductReview::factory()
                ->create([
                    'is_verified_purchase' => true,
                ]);

        ProductReview::factory()
            ->create([
                'is_verified_purchase' => false,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'verified' => 'yes',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $verified->id,
                    ),
            );
    }

    public function test_admin_index_can_filter_unverified_reviews(): void
    {
        $admin =
            $this->createAdmin();

        ProductReview::factory()
            ->create([
                'is_verified_purchase' => true,
            ]);

        $unverified =
            ProductReview::factory()
                ->create([
                    'is_verified_purchase' => false,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'verified' => 'no',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'reviews.data',
                        1,
                    )
                    ->where(
                        'reviews.data.0.id',
                        $unverified->id,
                    ),
            );
    }

    public function test_invalid_review_filters_are_rejected(): void
    {
        $admin =
            $this->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reviews.index',
                    [
                        'status' => 'invalid-status',

                        'rating' => 9,

                        'verified' => 'invalid',

                        'per_page' => 999,
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'status',
                'rating',
                'verified',
                'per_page',
            ]);
    }

    public function test_review_filters_are_preserved_in_paginated_links(): void
    {
        $admin =
            $this->createAdmin();

        ProductReview::factory()
            ->count(15)
            ->create([
                'status' => ProductReviewStatus::Pending,

                'rating' => 5,

                'is_verified_purchase' => true,
            ]);

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reviews.index',
                        [
                            'status' => ProductReviewStatus::Pending
                                ->value,

                            'rating' => 5,

                            'verified' => 'yes',

                            'per_page' => 10,
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'reviews.current_page',
                        1,
                    )
                    ->where(
                        'reviews.last_page',
                        2,
                    )
                    ->where(
                        'reviews.per_page',
                        10,
                    )
                    ->where(
                        'reviews.total',
                        15,
                    )
                    ->where(
                        'filters.status',
                        ProductReviewStatus::Pending
                            ->value,
                    )
                    ->where(
                        'filters.rating',
                        '5',
                    )
                    ->where(
                        'filters.verified',
                        'yes',
                    )
                    ->where(
                        'filters.per_page',
                        10,
                    ),
            );

        $payload =
            $response
                ->viewData(
                    'page',
                );

        $encoded =
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringContainsString(
            'status=pending',
            $encoded,
        );

        $this->assertStringContainsString(
            'rating=5',
            $encoded,
        );

        $this->assertStringContainsString(
            'verified=yes',
            $encoded,
        );

        $this->assertStringContainsString(
            'per_page=10',
            $encoded,
        );
    }

    public function test_pending_review_is_hidden_from_storefront_before_approval(): void
    {
        $admin =
            $this->createAdmin();

        $product =
            Product::factory()
                ->create();

        $review =
            ProductReview::factory()
                ->create([
                    'product_id' => $product->id,

                    'rating' => 5,

                    'status' => ProductReviewStatus::Pending,
                ]);

        $before =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            0,
            $before['summary']['review_count'],
        );

        $this->assertSame(
            [],
            $before['reviews']['data'],
        );

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
            )
            ->assertSessionHasNoErrors();

        $after =
            app(
                StorefrontProductReviewsQuery::class,
            )->get(
                product: $product,
            );

        $this->assertSame(
            1,
            $after['summary']['review_count'],
        );

        $this->assertSame(
            5.0,
            $after['summary']['average_rating'],
        );

        $this->assertCount(
            1,
            $after['reviews']['data'],
        );

        $this->assertSame(
            $review->id,
            $after['reviews']['data'][0]['id'],
        );
    }

    public function test_rejecting_approved_review_removes_it_from_storefront_aggregates(): void
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

                    'rating' => 5,
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
                    'admin.reviews.reject',
                    $review,
                ),
                [
                    'note' => 'Removed after moderation review.',
                ],
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

        $this->assertSame(
            [],
            $after['reviews']['data'],
        );
    }

    public function test_admin_manager_and_editor_roles_can_access_review_moderation(): void
    {
        foreach (
            [
                AdminRole::Admin,
                AdminRole::Manager,
                AdminRole::Editor,
            ] as $role
        ) {
            $admin =
                $this->createAdmin(
                    $role,
                );

            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reviews.index',
                    ),
                )
                ->assertOk();

            auth(
                'admin',
            )->logout();
        }
    }

    private function createAdmin(
        AdminRole $role = AdminRole::Admin,
    ): Admin {
        return Admin::factory()
            ->create([
                'role' => $role,

                'is_active' => true,
            ]);
    }
}
