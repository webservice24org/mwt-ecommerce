<?php

declare(strict_types=1);

namespace Tests\Feature\Wishlist;

use App\Domain\Wishlist\Services\WishlistService;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WishlistPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_add_uses_session_wishlist(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $product,
        );

        $this->assertSame(
            [
                $product->id,
            ],
            $service->ids(),
        );

        $this->assertTrue(
            $service->contains(
                product: $product,
            ),
        );

        $this->assertSame(
            1,
            $service->count(),
        );

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );
    }

    public function test_guest_add_is_idempotent(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $product,
        );

        $service->add(
            product: $product,
        );

        $this->assertSame(
            [
                $product->id,
            ],
            $service->ids(),
        );

        $this->assertSame(
            1,
            $service->count(),
        );

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );
    }

    public function test_guest_remove_uses_session_wishlist(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $product,
        );

        $service->remove(
            product: $product,
        );

        $this->assertFalse(
            $service->contains(
                product: $product,
            ),
        );

        $this->assertSame(
            [],
            $service->ids(),
        );

        $this->assertSame(
            0,
            $service->count(),
        );

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );
    }

    public function test_guest_toggle_adds_and_removes_session_item(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $added =
            $service->toggle(
                product: $product,
            );

        $this->assertTrue(
            $added,
        );

        $this->assertTrue(
            $service->contains(
                product: $product,
            ),
        );

        $removed =
            $service->toggle(
                product: $product,
            );

        $this->assertFalse(
            $removed,
        );

        $this->assertFalse(
            $service->contains(
                product: $product,
            ),
        );

        $this->assertSame(
            0,
            $service->count(),
        );
    }

    public function test_guest_clear_removes_all_session_wishlist_items(): void
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

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $firstProduct,
        );

        $service->add(
            product: $secondProduct,
        );

        $this->assertSame(
            2,
            $service->count(),
        );

        $service->clear();

        $this->assertSame(
            [],
            $service->ids(),
        );

        $this->assertSame(
            0,
            $service->count(),
        );
    }

    public function test_authenticated_add_creates_database_wishlist_entry(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $product,

            user: $user,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );

        $this->assertSame(
            [
                $product->id,
            ],
            $service->ids(
                user: $user,
            ),
        );

        $this->assertTrue(
            $service->contains(
                product: $product,

                user: $user,
            ),
        );

        $this->assertSame(
            1,
            $service->count(
                user: $user,
            ),
        );
    }

    public function test_authenticated_add_is_idempotent(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $product,

            user: $user,
        );

        $service->add(
            product: $product,

            user: $user,
        );

        $this->assertDatabaseCount(
            'wishlists',
            1,
        );

        $this->assertSame(
            1,
            $service->count(
                user: $user,
            ),
        );
    }

    public function test_authenticated_remove_removes_only_owned_database_entry(): void
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
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        Wishlist::factory()
            ->create([
                'user_id' => $otherUser->id,

                'product_id' => $product->id,
            ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->remove(
            product: $product,

            user: $user,
        );

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $otherUser->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_authenticated_toggle_adds_database_entry(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $active =
            $service->toggle(
                product: $product,

                user: $user,
            );

        $this->assertTrue(
            $active,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_authenticated_toggle_removes_database_entry(): void
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

        $service =
            app(
                WishlistService::class,
            );

        $active =
            $service->toggle(
                product: $product,

                user: $user,
            );

        $this->assertFalse(
            $active,
        );

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_authenticated_clear_removes_only_current_customers_rows(): void
    {
        $user =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

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

        Wishlist::factory()
            ->create([
                'user_id' => $otherUser->id,

                'product_id' => $firstProduct->id,
            ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->clear(
            user: $user,
        );

        $this->assertSame(
            0,
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count(),
        );

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

    public function test_guest_session_items_merge_into_authenticated_customer_database(): void
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

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $firstProduct,
        );

        $service->add(
            product: $secondProduct,
        );

        $this->assertSame(
            2,
            $service->count(),
        );

        $inserted =
            $service->mergeGuestInto(
                $user,
            );

        $this->assertSame(
            2,
            $inserted,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $firstProduct->id,
            ],
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $secondProduct->id,
            ],
        );

        $this->assertSame(
            2,
            $service->count(
                user: $user,
            ),
        );

        /*
         * Guest session must be cleared after
         * successful merge.
         */
        $this->assertSame(
            [],
            $service->ids(),
        );
    }

    public function test_existing_database_item_and_same_guest_item_do_not_duplicate_on_merge(): void
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

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $product,
        );

        $inserted =
            $service->mergeGuestInto(
                $user,
            );

        $this->assertSame(
            0,
            $inserted,
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
            $service->ids(),
        );
    }

    public function test_merge_preserves_existing_database_items_and_adds_new_guest_items(): void
    {
        $user =
            User::factory()
                ->create();

        $existingProduct =
            Product::factory()
                ->create();

        $guestProduct =
            Product::factory()
                ->create();

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $existingProduct->id,
            ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $guestProduct,
        );

        $inserted =
            $service->mergeGuestInto(
                $user,
            );

        $this->assertSame(
            1,
            $inserted,
        );

        $ids =
            $service->ids(
                user: $user,
            );

        sort(
            $ids,
        );

        $expected = [
            $existingProduct->id,
            $guestProduct->id,
        ];

        sort(
            $expected,
        );

        $this->assertSame(
            $expected,
            $ids,
        );
    }

    public function test_invalid_guest_session_ids_are_ignored_during_merge(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
                'invalid',
                -10,
                999999,
                null,
            ],
        ]);

        $service =
            app(
                WishlistService::class,
            );

        $inserted =
            $service->mergeGuestInto(
                $user,
            );

        $this->assertSame(
            1,
            $inserted,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'product_id' => 999999,
            ],
        );

        $this->assertSame(
            [],
            $service->ids(),
        );
    }

    public function test_merge_with_only_missing_products_clears_guest_session(): void
    {
        $user =
            User::factory()
                ->create();

        $this->withSession([
            'storefront.wishlist' => [
                999998,
                999999,
            ],
        ]);

        $service =
            app(
                WishlistService::class,
            );

        $inserted =
            $service->mergeGuestInto(
                $user,
            );

        $this->assertSame(
            0,
            $inserted,
        );

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );

        $this->assertSame(
            [],
            $service->ids(),
        );
    }

    public function test_authenticated_customer_ids_are_database_backed_not_session_backed(): void
    {
        $user =
            User::factory()
                ->create();

        $sessionProduct =
            Product::factory()
                ->create();

        $databaseProduct =
            Product::factory()
                ->create();

        $this->withSession([
            'storefront.wishlist' => [
                $sessionProduct->id,
            ],
        ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $databaseProduct->id,
            ]);

        $service =
            app(
                WishlistService::class,
            );

        $this->assertSame(
            [
                $databaseProduct->id,
            ],
            $service->ids(
                user: $user,
            ),
        );

        $this->assertTrue(
            $service->contains(
                product: $databaseProduct,

                user: $user,
            ),
        );

        $this->assertFalse(
            $service->contains(
                product: $sessionProduct,

                user: $user,
            ),
        );
    }

    public function test_customer_a_cannot_affect_customer_b_wishlist(): void
    {
        $customerA =
            User::factory()
                ->create();

        $customerB =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        Wishlist::factory()
            ->create([
                'user_id' => $customerB->id,

                'product_id' => $product->id,
            ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->remove(
            product: $product,

            user: $customerA,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $customerB->id,

                'product_id' => $product->id,
            ],
        );

        $this->assertSame(
            0,
            $service->count(
                user: $customerA,
            ),
        );

        $this->assertSame(
            1,
            $service->count(
                user: $customerB,
            ),
        );
    }

    public function test_guest_toggle_endpoint_returns_active_true_and_correct_count(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $this
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

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );
    }

    public function test_guest_toggle_endpoint_returns_active_false_after_second_toggle(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $this
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
    }

    public function test_authenticated_toggle_endpoint_persists_database_row(): void
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

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_authenticated_toggle_endpoint_removes_database_row_on_second_toggle(): void
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

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_authenticated_toggle_merges_guest_session_before_toggling(): void
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

        /*
         * Guest previously wishlisted both.
         */
        $this->withSession([
            'storefront.wishlist' => [
                $firstProduct->id,
                $secondProduct->id,
            ],
        ]);

        /*
         * After login, toggling first product must:
         *
         * 1. merge [first, second] to DB,
         * 2. then remove first,
         * 3. leaving only second.
         */
        $this
            ->actingAs(
                $user,
            )
            ->postJson(
                route(
                    'wishlist.toggle',
                    $firstProduct,
                ),
            )
            ->assertOk()
            ->assertJson([
                'active' => false,

                'count' => 1,
            ]);

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $firstProduct->id,
            ],
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $secondProduct->id,
            ],
        );

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );
    }

    public function test_authenticated_wishlist_index_merges_guest_session_into_database(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
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
            ->assertOk();

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );

        $this->assertSame(
            [],
            app(
                WishlistService::class,
            )->ids(),
        );
    }

    public function test_authenticated_destroy_endpoint_removes_only_own_item(): void
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
                    'status' => 'published',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
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

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'user_id' => $otherUser->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_authenticated_clear_endpoint_removes_only_own_wishlist(): void
    {
        $user =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

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

        Wishlist::factory()
            ->create([
                'user_id' => $otherUser->id,

                'product_id' => $firstProduct->id,
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

        $this->assertSame(
            0,
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count(),
        );

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

    public function test_published_product_can_be_toggled(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $this
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertOk()
            ->assertJson([
                'active' => true,
            ]);
    }

    public function test_unpublished_product_cannot_be_toggled(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => 'draft',
                ]);

        $this
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );
    }

    public function test_guest_products_returns_only_published_products(): void
    {
        $published =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $draft =
            Product::factory()
                ->create([
                    'status' => 'draft',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        /*
         * add() itself stores IDs. products() is responsible
         * for enforcing storefront visibility.
         */
        $service->add(
            product: $published,
        );

        $service->add(
            product: $draft,
        );

        $products =
            $service->products();

        $this->assertSame(
            [
                $published->id,
            ],
            $products
                ->pluck('id')
                ->all(),
        );
    }

    public function test_authenticated_products_returns_only_published_products(): void
    {
        $user =
            User::factory()
                ->create();

        $published =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $draft =
            Product::factory()
                ->create([
                    'status' => 'draft',
                ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $published->id,
            ]);

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $draft->id,
            ]);

        $products =
            app(
                WishlistService::class,
            )->products(
                user: $user,
            );

        $this->assertSame(
            [
                $published->id,
            ],
            $products
                ->pluck('id')
                ->all(),
        );
    }

    public function test_authenticated_wishlist_persists_across_new_session(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => 'published',
                ]);

        $service =
            app(
                WishlistService::class,
            );

        $service->add(
            product: $product,

            user: $user,
        );

        /*
         * Simulate session wishlist disappearing.
         * Persistent account wishlist must remain.
         */
        session()->flush();

        $this->assertSame(
            1,
            $service->count(
                user: $user,
            ),
        );

        $this->assertTrue(
            $service->contains(
                product: $product,

                user: $user,
            ),
        );
    }
}
