<?php

declare(strict_types=1);

namespace Tests\Feature\Wishlist;

use App\Domain\Wishlist\Services\WishlistService;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontWishlistStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_shared_wishlist_state_contains_session_product_ids(): void
    {
        $firstProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $secondProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $firstProduct->id,
                $secondProduct->id,
            ],
        ]);

        $response =
            $this->get(
                route(
                    'wishlist.index',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        2,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $firstProduct->id,
                            $secondProduct->id,
                        ],
                    ),
            );
    }

    public function test_guest_shared_wishlist_state_normalizes_duplicate_session_ids(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
                $product->id,
                (string) $product->id,
            ],
        ]);

        $this
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $product->id,
                        ],
                    ),
            );
    }

    public function test_guest_shared_wishlist_state_ignores_invalid_session_values(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
                'invalid',
                -10,
                0,
                null,
            ],
        ]);

        $this
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $product->id,
                        ],
                    ),
            );
    }

    public function test_authenticated_shared_wishlist_state_contains_database_ids(): void
    {
        $user =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $secondProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $firstProduct->id,
            ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $secondProduct->id,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        2,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $firstProduct->id,
                            $secondProduct->id,
                        ],
                    ),
            );
    }

    public function test_authenticated_shared_state_merges_guest_session_before_exposing_ids(): void
    {
        $user =
            User::factory()
                ->create();

        $existingProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $guestProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $existingProduct->id,
            ]);

        $this->withSession([
            'storefront.wishlist' => [
                $guestProduct->id,
            ],
        ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        2,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $existingProduct->id,
                            $guestProduct->id,
                        ],
                    ),
            );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $guestProduct->id,
            ],
        );

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );
    }

    public function test_authenticated_merge_does_not_duplicate_existing_database_item(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
            ],
        ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $product->id,
                        ],
                    ),
            );

        $this->assertSame(
            1,
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'product_id',
                    $product->id,
                )
                ->count(),
        );
    }

    public function test_other_customers_wishlist_ids_are_never_exposed(): void
    {
        $user =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $ownProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $otherProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $ownProduct->id,
            ]);

        Wishlist::factory()
            ->create([
                'user_id' => $otherUser->id,

                'product_id' => $otherProduct->id,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $ownProduct->id,
                        ],
                    ),
            );
    }

    public function test_shared_wishlist_count_matches_authoritative_unique_ids(): void
    {
        $user =
            User::factory()
                ->create();

        $products =
            Product::factory()
                ->count(3)
                ->create([
                    'status' => 'published',
                ]);

        foreach (
            $products as $product
        ) {
            Wishlist::factory()
                ->create([
                    'user_id' => $user->id,

                    'product_id' => $product->id,
                ]);
        }

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        3,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        $products
                            ->pluck('id')
                            ->all(),
                    ),
            );
    }

    public function test_product_listing_page_receives_shared_wishlist_state(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                '/products',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'storefrontWishlist',
                    )
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $product->id,
                        ],
                    ),
            );
    }

    public function test_product_detail_page_receives_shared_wishlist_state(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                "/products/{$product->slug}",
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'storefrontWishlist',
                    )
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $product->id,
                        ],
                    ),
            );
    }

    public function test_authenticated_database_wishlist_persists_between_requests(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    ),
            );

        $this
            ->actingAs(
                $user,
            )
            ->get(
                "/products/{$product->slug}",
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $product->id,
                        ],
                    ),
            );
    }

    public function test_removing_database_wishlist_item_changes_shared_state_on_next_request(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $wishlist =
            Wishlist::factory()
                ->create([
                    'user_id' => $user->id,

                    'product_id' => $product->id,
                ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'storefrontWishlist.count',
                    1,
                ),
            );

        $wishlist->delete();

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        0,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [],
                    ),
            );
    }

    public function test_deleting_product_removes_it_from_authenticated_shared_wishlist_state(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        /*
         * Product uses SoftDeletes in this project.
         *
         * Force-delete it so the product FK cascade
         * physically removes the wishlist row.
         */
        $product->forceDelete();

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        0,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [],
                    ),
            );
    }

    public function test_database_wishlist_is_authoritative_for_authenticated_user_after_guest_merge(): void
    {
        $user =
            User::factory()
                ->create();

        $databaseProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $guestProduct =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $databaseProduct->id,
            ]);

        $this->withSession([
            'storefront.wishlist' => [
                $guestProduct->id,
            ],
        ]);

        /*
         * First authenticated request performs the
         * one-way session -> database merge.
         */
        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $databaseProduct->id,
            ],
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $guestProduct->id,
            ],
        );

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );

        /*
         * Following requests now read persistent
         * account state, not the old guest session.
         */
        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        2,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $databaseProduct->id,
                            $guestProduct->id,
                        ],
                    ),
            );
    }

    public function test_shared_state_updates_after_authenticated_toggle_request_and_following_inertia_request(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $this
            ->actingAs(
                $user,
            )
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertOk()
            ->assertJson([
                'active' => true,

                'count' => 1,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                "/products/{$product->slug}",
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        1,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [
                            $product->id,
                        ],
                    ),
            );
    }

    public function test_shared_state_updates_after_authenticated_remove_toggle(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertOk()
            ->assertJson([
                'active' => false,

                'count' => 0,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->get(
                "/products/{$product->slug}",
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontWishlist.count',
                        0,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [],
                    ),
            );
    }
}
