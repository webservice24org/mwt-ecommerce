<?php

declare(strict_types=1);

namespace Tests\Feature\Wishlist;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class WishlistFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_wishlists_table_has_expected_columns(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'wishlists',
            ),
        );

        $this->assertTrue(
            Schema::hasColumns(
                'wishlists',
                [
                    'id',
                    'user_id',
                    'product_id',
                    'created_at',
                    'updated_at',
                ],
            ),
        );
    }

    public function test_wishlist_factory_creates_valid_entry(): void
    {
        $wishlist =
            Wishlist::factory()
                ->create();

        $this->assertGreaterThan(
            0,
            $wishlist->id,
        );

        $this->assertGreaterThan(
            0,
            $wishlist->user_id,
        );

        $this->assertGreaterThan(
            0,
            $wishlist->product_id,
        );

        $this->assertNotNull(
            $wishlist->created_at,
        );

        $this->assertNotNull(
            $wishlist->updated_at,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'id' => $wishlist->id,

                'user_id' => $wishlist->user_id,

                'product_id' => $wishlist->product_id,
            ],
        );
    }

    public function test_wishlist_belongs_to_user(): void
    {
        $user =
            User::factory()
                ->create();

        $wishlist =
            Wishlist::factory()
                ->for(
                    $user,
                )
                ->create();

        $this->assertTrue(
            $wishlist
                ->user
                ->is(
                    $user,
                ),
        );

        $this->assertSame(
            $user->id,
            $wishlist
                ->user
                ->id,
        );
    }

    public function test_wishlist_belongs_to_product(): void
    {
        $product =
            Product::factory()
                ->create();

        $wishlist =
            Wishlist::factory()
                ->for(
                    $product,
                )
                ->create();

        $this->assertTrue(
            $wishlist
                ->product
                ->is(
                    $product,
                ),
        );

        $this->assertSame(
            $product->id,
            $wishlist
                ->product
                ->id,
        );
    }

    public function test_user_has_many_wishlist_entries(): void
    {
        $user =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

        $firstWishlist =
            Wishlist::factory()
                ->create([
                    'user_id' => $user->id,

                    'product_id' => $firstProduct->id,
                ]);

        $secondWishlist =
            Wishlist::factory()
                ->create([
                    'user_id' => $user->id,

                    'product_id' => $secondProduct->id,
                ]);

        Wishlist::factory()
            ->create();

        $ids =
            $user
                ->wishlists()
                ->orderBy('id')
                ->pluck('id')
                ->all();

        $this->assertSame(
            [
                $firstWishlist->id,
                $secondWishlist->id,
            ],
            $ids,
        );
    }

    public function test_product_has_many_wishlist_entries(): void
    {
        $product =
            Product::factory()
                ->create();

        $firstUser =
            User::factory()
                ->create();

        $secondUser =
            User::factory()
                ->create();

        $firstWishlist =
            Wishlist::factory()
                ->create([
                    'user_id' => $firstUser->id,

                    'product_id' => $product->id,
                ]);

        $secondWishlist =
            Wishlist::factory()
                ->create([
                    'user_id' => $secondUser->id,

                    'product_id' => $product->id,
                ]);

        Wishlist::factory()
            ->create();

        $ids =
            $product
                ->wishlists()
                ->orderBy('id')
                ->pluck('id')
                ->all();

        $this->assertSame(
            [
                $firstWishlist->id,
                $secondWishlist->id,
            ],
            $ids,
        );
    }

    public function test_same_customer_cannot_wishlist_same_product_twice(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this->expectException(
            QueryException::class,
        );

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);
    }

    public function test_same_product_can_be_wishlisted_by_different_customers(): void
    {
        $product =
            Product::factory()
                ->create();

        $firstUser =
            User::factory()
                ->create();

        $secondUser =
            User::factory()
                ->create();

        Wishlist::factory()
            ->create([
                'user_id' => $firstUser->id,

                'product_id' => $product->id,
            ]);

        Wishlist::factory()
            ->create([
                'user_id' => $secondUser->id,

                'product_id' => $product->id,
            ]);

        $this->assertDatabaseCount(
            'wishlists',
            2,
        );
    }

    public function test_customer_can_wishlist_multiple_products(): void
    {
        $user =
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

        $this->assertSame(
            2,
            Wishlist::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count(),
        );
    }

    public function test_deleting_user_cascades_owned_wishlist_entries(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this->assertDatabaseCount(
            'wishlists',
            1,
        );

        /*
         * Direct database delete tests the actual FK
         * cascade independently of model events or
         * possible soft-delete behavior.
         */
        DB::table('users')
            ->where(
                'id',
                $user->id,
            )
            ->delete();

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );
    }

    public function test_physically_deleting_product_cascades_wishlist_entries(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        Wishlist::factory()
            ->create([
                'user_id' => $user->id,

                'product_id' => $product->id,
            ]);

        $this->assertDatabaseCount(
            'wishlists',
            1,
        );

        /*
         * Use a physical DB delete so this test remains
         * correct even if Product uses SoftDeletes.
         */
        DB::table('products')
            ->where(
                'id',
                $product->id,
            )
            ->delete();

        $this->assertDatabaseCount(
            'wishlists',
            0,
        );
    }

    public function test_deleting_wishlist_entry_does_not_delete_user_or_product(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $wishlist =
            Wishlist::factory()
                ->create([
                    'user_id' => $user->id,

                    'product_id' => $product->id,
                ]);

        $wishlist->delete();

        $this->assertDatabaseMissing(
            'wishlists',
            [
                'id' => $wishlist->id,
            ],
        );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
            ],
        );

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
            ],
        );
    }

    public function test_wishlist_entry_can_be_recreated_after_removal(): void
    {
        $user =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $wishlist =
            Wishlist::factory()
                ->create([
                    'user_id' => $user->id,

                    'product_id' => $product->id,
                ]);

        $wishlist->delete();

        $replacement =
            Wishlist::factory()
                ->create([
                    'user_id' => $user->id,

                    'product_id' => $product->id,
                ]);

        $this->assertDatabaseCount(
            'wishlists',
            1,
        );

        $this->assertDatabaseHas(
            'wishlists',
            [
                'id' => $replacement->id,

                'user_id' => $user->id,

                'product_id' => $product->id,
            ],
        );
    }

    public function test_user_and_product_ids_are_cast_to_integers(): void
    {
        $wishlist =
            Wishlist::factory()
                ->create();

        $this->assertIsInt(
            $wishlist->user_id,
        );

        $this->assertIsInt(
            $wishlist->product_id,
        );
    }
}
