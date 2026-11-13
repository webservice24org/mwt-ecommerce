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

final class WishlistSharedStateRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_shared_count_equals_normalized_unique_session_ids(): void
    {
        $firstProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        $secondProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        $this->withSession([
            'storefront.wishlist' => [
                $firstProduct->id,
                $firstProduct->id,
                (string) $secondProduct->id,
                'invalid',
                0,
                -10,
                null,
            ],
        ]);

        $this
            ->get(route('wishlist.index'))
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

    public function test_authenticated_shared_count_equals_only_owned_database_rows(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        $secondProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        $otherProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $firstProduct->id,
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $secondProduct->id,
        ]);

        Wishlist::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $otherProduct->id,
        ]);

        $this
            ->actingAs($user)
            ->get(route('wishlist.index'))
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

    public function test_guest_to_authenticated_merge_exposes_final_merged_count_and_ids(): void
    {
        $user = User::factory()->create();

        $databaseProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        $guestProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $databaseProduct->id,
        ]);

        $this->withSession([
            'storefront.wishlist' => [
                $guestProduct->id,
            ],
        ]);

        $this
            ->actingAs($user)
            ->get(route('wishlist.index'))
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

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $guestProduct->id,
        ]);

        $this->assertSame(
            [],
            app(WishlistService::class)->ids(),
        );
    }

    public function test_authenticated_database_state_remains_authoritative_after_merge(): void
    {
        $user = User::factory()->create();

        $databaseProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        $guestProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $databaseProduct->id,
        ]);

        $this->withSession([
            'storefront.wishlist' => [
                $guestProduct->id,
            ],
        ]);

        $this
            ->actingAs($user)
            ->get(route('wishlist.index'))
            ->assertOk();

        /*
         * Simulate stale guest-session data after the merge.
         * Authenticated shared state must still come from DB.
         */
        session()->put(
            'storefront.wishlist',
            [],
        );

        $this
            ->actingAs($user)
            ->get(route('wishlist.index'))
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

    public function test_toggle_add_returns_immediate_authoritative_count(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'status' => 'published',
        ]);

        $this
            ->actingAs($user)
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

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_toggle_remove_returns_immediate_authoritative_count(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'status' => 'published',
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertOk()
            ->assertExactJson([
                'active' => false,
                'count' => 0,
            ]);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_remove_followed_by_inertia_request_exposes_updated_shared_state(): void
    {
        $user = User::factory()->create();

        $firstProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        $secondProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $firstProduct->id,
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $secondProduct->id,
        ]);

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'wishlist.destroy',
                    $firstProduct,
                ),
            )
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->get(route('wishlist.index'))
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
                            $secondProduct->id,
                        ],
                    ),
            );
    }

    public function test_clear_followed_by_inertia_request_exposes_zero_and_empty_ids(): void
    {
        $user = User::factory()->create();

        $firstProduct = Product::factory()->create();
        $secondProduct = Product::factory()->create();

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $firstProduct->id,
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $secondProduct->id,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('wishlist.clear'))
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->get(route('wishlist.index'))
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

    public function test_repeated_toggle_cycle_keeps_database_and_count_consistent(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'status' => 'published',
        ]);

        $this
            ->actingAs($user)
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

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'wishlist.toggle',
                    $product,
                ),
            )
            ->assertOk()
            ->assertExactJson([
                'active' => false,
                'count' => 0,
            ]);

        $this
            ->actingAs($user)
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

    public function test_account_wishlist_state_persists_across_multiple_requests(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'status' => 'published',
        ]);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        foreach (range(1, 3) as $requestNumber) {
            $this
                ->actingAs($user)
                ->get(route('wishlist.index'))
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
    }

    public function test_guest_toggle_response_and_next_inertia_shared_state_are_consistent(): void
    {
        $product = Product::factory()->create([
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
            ->assertExactJson([
                'active' => true,
                'count' => 1,
            ]);

        $this
            ->get(route('wishlist.index'))
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

    public function test_authenticated_toggle_does_not_change_another_customers_count(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()->create([
            'status' => 'published',
        ]);

        $otherProduct = Product::factory()->create([
            'status' => 'published',
        ]);

        Wishlist::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $otherProduct->id,
        ]);

        $this
            ->actingAs($user)
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
}
