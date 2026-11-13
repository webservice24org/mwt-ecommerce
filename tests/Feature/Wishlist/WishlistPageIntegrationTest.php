<?php

declare(strict_types=1);

namespace Tests\Feature\Wishlist;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class WishlistPageIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_wishlist_page_returns_session_items(): void
    {
        $firstProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $secondProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $firstProduct->id,
                $secondProduct->id,
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
                    ->component(
                        'Frontend/Wishlist/Index',
                    )
                    ->has(
                        'items',
                        2,
                    )
                    ->where(
                        'items.0.product.id',
                        $firstProduct->id,
                    )
                    ->where(
                        'items.1.product.id',
                        $secondProduct->id,
                    ),
            );
    }

    public function test_authenticated_wishlist_page_returns_persistent_database_items(): void
    {
        $user =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
                ]);

        $secondProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,
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
                    ->has(
                        'items',
                        2,
                    )
                    ->where(
                        'items.0.product.id',
                        $firstProduct->id,
                    )
                    ->where(
                        'items.1.product.id',
                        $secondProduct->id,
                    ),
            );
    }

    public function test_wishlist_item_payload_contains_availability_metadata(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Simple,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
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
                        'items.0.product.id',
                        $product->id,
                    )
                    ->where(
                        'items.0.availability',
                        'available',
                    )
                    ->where(
                        'items.0.availability_label',
                        'Available',
                    )
                    ->where(
                        'items.0.can_add_to_cart',
                        true,
                    ),
            );
    }

    public function test_published_simple_product_is_directly_addable_to_cart(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Simple,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
            ],
        ]);

        $this
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'items.0.availability',
                        'available',
                    )
                    ->where(
                        'items.0.can_add_to_cart',
                        true,
                    ),
            );
    }

    public function test_published_variable_product_requires_option_selection(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Variable,
                ]);

        ProductVariant::factory()
            ->for(
                $product,
            )
            ->create([
                'is_active' => true,
            ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
            ],
        ]);

        $this
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'items.0.product.id',
                        $product->id,
                    )
                    ->where(
                        'items.0.availability',
                        'requires_options',
                    )
                    ->where(
                        'items.0.availability_label',
                        'Choose options',
                    )
                    ->where(
                        'items.0.can_add_to_cart',
                        false,
                    ),
            );
    }

    public function test_draft_product_remains_visible_as_unavailable(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Draft,

                    'type' => ProductType::Simple,
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
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
                    ->has(
                        'items',
                        1,
                    )
                    ->where(
                        'items.0.product.id',
                        $product->id,
                    )
                    ->where(
                        'items.0.availability',
                        'unavailable',
                    )
                    ->where(
                        'items.0.availability_label',
                        'Unavailable',
                    )
                    ->where(
                        'items.0.can_add_to_cart',
                        false,
                    ),
            );
    }

    public function test_future_scheduled_product_is_unavailable(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Simple,

                    'published_at' => now()
                        ->addDay(),
                ]);

        $this->withSession([
            'storefront.wishlist' => [
                $product->id,
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
                        'items.0.availability',
                        'unavailable',
                    )
                    ->where(
                        'items.0.can_add_to_cart',
                        false,
                    ),
            );
    }

    public function test_remove_endpoint_deletes_only_current_customers_wishlist_item(): void
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

    public function test_clear_endpoint_deletes_only_current_customers_wishlist_items(): void
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

    public function test_other_customers_wishlist_items_never_render(): void
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
                    ->has(
                        'items',
                        1,
                    )
                    ->where(
                        'items.0.product.id',
                        $ownProduct->id,
                    ),
            );
    }

    public function test_empty_wishlist_page_returns_empty_items_array(): void
    {
        $this
            ->get(
                route(
                    'wishlist.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Wishlist/Index',
                    )
                    ->where(
                        'items',
                        [],
                    ),
            );
    }

    public function test_simple_wishlist_product_can_be_added_to_cart_through_existing_cart_endpoint(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Simple,
                ]);

        $this
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
            ->assertOk()
            ->assertJsonPath(
                'item_count',
                1,
            );
    }

    public function test_adding_wishlist_product_to_cart_does_not_remove_wishlist_entry(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Simple,
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

    public function test_unpublished_product_cannot_be_added_to_cart(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Draft,

                    'type' => ProductType::Simple,
                ]);

        $this
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
            ->assertNotFound();
    }

    public function test_variable_product_without_variant_is_rejected_by_cart_endpoint(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Variable,
                ]);

        ProductVariant::factory()
            ->for(
                $product,
            )
            ->create([
                'is_active' => true,
            ]);

        $this
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
            ->assertStatus(
                422,
            );
    }

    public function test_variable_product_with_active_variant_can_be_added_to_cart(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Variable,
                ]);

        $variant =
            ProductVariant::factory()
                ->for(
                    $product,
                )
                ->create([
                    'is_active' => true,
                ]);

        $this
            ->postJson(
                route(
                    'cart.items.store',
                ),
                [
                    'product_id' => $product->id,

                    'variant_id' => $variant->id,

                    'quantity' => 1,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'item_count',
                1,
            );
    }

    public function test_variant_from_another_product_cannot_be_used_when_adding_to_cart(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Variable,
                ]);

        $otherProduct =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Variable,
                ]);

        $otherVariant =
            ProductVariant::factory()
                ->for(
                    $otherProduct,
                )
                ->create([
                    'is_active' => true,
                ]);

        $this
            ->postJson(
                route(
                    'cart.items.store',
                ),
                [
                    'product_id' => $product->id,

                    'variant_id' => $otherVariant->id,

                    'quantity' => 1,
                ],
            )
            ->assertNotFound();
    }

    public function test_inactive_variant_cannot_be_added_to_cart(): void
    {
        $product =
            Product::factory()
                ->create([
                    'status' => ProductStatus::Published,

                    'type' => ProductType::Variable,
                ]);

        $variant =
            ProductVariant::factory()
                ->for(
                    $product,
                )
                ->create([
                    'is_active' => false,
                ]);

        $this
            ->postJson(
                route(
                    'cart.items.store',
                ),
                [
                    'product_id' => $product->id,

                    'variant_id' => $variant->id,

                    'quantity' => 1,
                ],
            )
            ->assertNotFound();
    }

    public function test_authenticated_wishlist_page_merges_guest_items_before_rendering(): void
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
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'items',
                        2,
                    ),
            );

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
    }
}
