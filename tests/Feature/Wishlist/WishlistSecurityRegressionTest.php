<?php

declare(strict_types=1);

namespace Tests\Feature\Wishlist;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Wishlist\Services\WishlistService;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class WishlistSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_remove_another_customers_wishlist_row(): void
    {
        $user =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $otherUser->id,

                'product_id' => $product->id,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'wishlist.destroy',
                    $product,
                ),
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $otherUser->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_customer_clear_does_not_remove_another_customers_rows(): void
    {
        $user =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $userProduct =
            Product::factory()
                ->create();

        $otherProduct =
            Product::factory()
                ->create();

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $userProduct->id,
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
            ->delete(
                route(
                    'wishlist.clear',
                ),
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $userProduct->id,
            ],
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $otherUser->id,

                'product_id' => $otherProduct->id,
            ],
        );
    }

    public function test_customer_cannot_see_another_customers_shared_wishlist_ids(): void
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
                    'status' => ProductStatus::Published,
                ]);

        $otherProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
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

    public function test_malformed_guest_session_ids_are_normalized_and_ignored(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
                (string) $product->id,
                $product->id,
                'invalid',
                '',
                0,
                -1,
                null,
                false,
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

    public function test_missing_product_route_returns_not_found_for_toggle(): void
    {
        $this
            ->postJson(
                '/wishlist/999999999/toggle',
            )
            ->assertNotFound();
    }

    public function test_draft_product_cannot_be_toggled_from_public_endpoint(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Draft,
                ]);

        $this
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertNotFound();

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );
    }

    public function test_future_scheduled_product_cannot_be_toggled_from_public_endpoint(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->addDay(),
                ]);

        $this
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertNotFound();

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );
    }

    public function test_available_published_product_can_be_toggled(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $this
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertOk()
            ->assertExactJson([
                'active' => true,

                'count' => 1,
            ]);
    }

    public function test_repeated_authenticated_add_cannot_create_duplicate_database_rows(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $wishlist =
            app(
                WishlistService::class,
            );

        $wishlist->add(
            product: $product,

            user: $user,
        );

        $wishlist->add(
            product: $product,

            user: $user,
        );

        $wishlist->add(
            product: $product,

            user: $user,
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

    public function test_repeated_toggle_cycle_remains_consistent(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
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
            ->assertExactJson([
                'active' => true,

                'count' => 1,
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
            ->assertExactJson([
                'active' => false,

                'count' => 0,
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
            ->assertExactJson([
                'active' => true,

                'count' => 1,
            ]);

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

    public function test_guest_and_account_duplicate_merge_creates_only_one_database_row(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
                $product->id,
                (string) $product->id,
            ],
        ]);

        $wishlist =
            app(
                WishlistService::class,
            );

        $wishlist->mergeGuestInto(
            $user,
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

        $this->assertSame(
            [],
            $wishlist->ids(),
        );
    }

    public function test_guest_merge_ignores_missing_product_ids(): void
    {
        $user =
            User::factory()
                ->create();

        $validProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $validProduct->id,
                999999999,
            ],
        ]);

        $wishlist =
            app(
                WishlistService::class,
            );

        $wishlist->mergeGuestInto(
            $user,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $validProduct->id,
            ],
        );

        $this->assertSame(
            1,
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count(),
        );

        $this->assertSame(
            [],
            $wishlist->ids(),
        );
    }

    public function test_guest_session_is_cleared_after_successful_authenticated_merge(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
            ],
        ]);

        $wishlist =
            app(
                WishlistService::class,
            );

        $merged =
            $wishlist->mergeGuestInto(
                $user,
            );

        $this->assertSame(
            1,
            $merged,
        );

        $this->assertSame(
            [],
            $wishlist->ids(),
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_guest_session_does_not_override_database_after_authenticated_merge(): void
    {
        $user =
            User::factory()
                ->create();

        $databaseProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $guestProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
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

        /*
         * Simulate stale/tampered guest state after
         * the successful account merge.
         */
        session()->put(
            'storefront.wishlist',
            [],
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

    public function test_remove_is_idempotent_for_guest(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
            ],
        ]);

        $this
            ->delete(
                route(
                    'wishlist.destroy',
                    $product,
                ),
            )
            ->assertRedirect();

        $this
            ->delete(
                route(
                    'wishlist.destroy',
                    $product,
                ),
            )
            ->assertRedirect();

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );
    }

    public function test_remove_is_idempotent_for_authenticated_customer(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
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
            ->delete(
                route(
                    'wishlist.destroy',
                    $product,
                ),
            )
            ->assertRedirect();

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'wishlist.destroy',
                    $product,
                ),
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_clear_is_idempotent_for_guest(): void
    {
        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

        $this->withSession([
            'storefront.wishlist' => [
                $firstProduct->id,
                $secondProduct->id,
            ],
        ]);

        $this
            ->delete(
                route(
                    'wishlist.clear',
                ),
            )
            ->assertRedirect();

        $this
            ->delete(
                route(
                    'wishlist.clear',
                ),
            )
            ->assertRedirect();

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );
    }

    public function test_clear_is_idempotent_for_authenticated_customer(): void
    {
        $user =
            User::factory()
                ->create();

        $products =
            Product::factory()
                ->count(
                    2,
                )
                ->create();

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
            ->delete(
                route(
                    'wishlist.clear',
                ),
            )
            ->assertRedirect();

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'wishlist.clear',
                ),
            )
            ->assertRedirect();

        $this->assertSame(
            0,
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count(),
        );
    }

    public function test_mutating_one_customer_does_not_change_another_customers_count(): void
    {
        $user =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $userProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $otherProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
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
            ->postJson(
                route(
                    'wishlist.toggle',
                    $userProduct,
                ),
            )
            ->assertExactJson([
                'active' => true,

                'count' => 1,
            ]);

        $this->assertSame(
            1,
            Wishlist::query()
                ->where(
                    'user_id',
                    $otherUser->id,
                )
                ->count(),
        );
    }

    public function test_stale_unavailable_wishlist_item_remains_removable(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Draft,
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
                        'items.0.product.id',
                        $product->id,
                    )
                    ->where(
                        'items.0.availability',
                        'unavailable',
                    )
                    ->where(
                        'items.0.can_add_to_cart',
                        false,
                    ),
            );

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'wishlist.destroy',
                    $product,
                ),
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_deleted_product_does_not_leak_into_authenticated_shared_state(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        /*
         * Physical deletion should trigger the
         * wishlist FK cascade.
         */
        $product->forceDelete();

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
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

    public function test_adding_to_cart_does_not_mutate_authenticated_wishlist(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => 'simple',
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
                    'cart.items.store',
                ),
                [
                    'product_id' => $product->id,

                    'variant_id' => null,

                    'quantity' => 1,
                ],
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_authenticated_wishlist_persists_across_fresh_session_boundary(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
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

        /*
         * Start with a clean session while retaining
         * the same authenticated account.
         */
        $this->flushSession();

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
    }

    public function test_guest_wishlist_does_not_expose_authenticated_account_items_after_logout_boundary(): void
    {
        $user =
            User::factory()
                ->create();

        $accountProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $accountProduct->id,
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
                fn (Assert $page) => $page->where(
                    'storefrontWishlist.count',
                    1,
                ),
            );

        auth()
            ->logout();

        $this->flushSession();

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
                        0,
                    )
                    ->where(
                        'storefrontWishlist.product_ids',
                        [],
                    ),
            );
    }
}
