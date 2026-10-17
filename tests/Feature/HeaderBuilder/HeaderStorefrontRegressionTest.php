<?php

declare(strict_types=1);

namespace Tests\Feature\HeaderBuilder;

use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryMenuQuery;
use App\Models\Category;
use App\Models\HeaderSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class HeaderStorefrontRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_shares_enabled_header_configuration(): void
    {
        HeaderSetting::query()->create([
            'singleton_key' => 'global',
            'template' => 'mega_menu',
            'is_enabled' => true,
            'config' => [
                'brand' => [
                    'name' => 'Regression Store',
                ],
            ],
        ]);

        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'storefrontHeader',
                    )
                    ->where(
                        'storefrontHeader.is_enabled',
                        true,
                    )
                    ->where(
                        'storefrontHeader.template',
                        'mega_menu',
                    ),
            );
    }

    public function test_disabled_header_is_shared_as_disabled_and_not_treated_as_active(): void
    {
        HeaderSetting::query()->create([
            'singleton_key' => 'global',
            'template' => 'mega_menu',
            'is_enabled' => false,
            'config' => [],
        ]);

        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'storefrontHeader',
                    )
                    ->where(
                        'storefrontHeader.is_enabled',
                        false,
                    ),
            );
    }

    public function test_storefront_header_contains_normalized_mega_menu_configuration(): void
    {
        HeaderSetting::query()->create([
            'singleton_key' => 'global',
            'template' => 'mega_menu',
            'is_enabled' => true,
            'config' => [],
        ]);

        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontHeader.template',
                        'mega_menu',
                    )
                    ->has(
                        'storefrontHeader.config',
                    )
                    ->has(
                        'storefrontHeader.config.brand',
                    )
                    ->has(
                        'storefrontHeader.config.search',
                    )
                    ->has(
                        'storefrontHeader.config.navigation',
                    )
                    ->has(
                        'storefrontHeader.config.mega_menu',
                    )
                    ->has(
                        'storefrontHeader.config.actions',
                    )
                    ->has(
                        'storefrontHeader.config.mobile',
                    ),
            );
    }

    public function test_storefront_shares_real_category_menu_data(): void
    {
        $root = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'parent_id' => $root->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'storefrontCategoryMenu',
                        1,
                    )
                    ->where(
                        'storefrontCategoryMenu.0.name',
                        'Electronics',
                    )
                    ->where(
                        'storefrontCategoryMenu.0.slug',
                        'electronics',
                    )
                    ->has(
                        'storefrontCategoryMenu.0.children',
                        1,
                    )
                    ->where(
                        'storefrontCategoryMenu.0.children.0.name',
                        'Phones',
                    )
                    ->where(
                        'storefrontCategoryMenu.0.children.0.slug',
                        'phones',
                    ),
            );
    }

    public function test_storefront_category_menu_shared_prop_matches_query_contract(): void
    {
        Category::factory()->create([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        $expected = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontCategoryMenu',
                        $expected,
                    ),
            );
    }

    public function test_guest_storefront_shares_wishlist_summary(): void
    {
        $response = $this->get(
            route('home'),
        );

        $response
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

    public function test_guest_storefront_shares_cart_summary(): void
    {
        $expected = app(
            CartService::class,
        )->summary();

        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontCart.item_count',
                        $expected->itemCount,
                    )
                    ->where(
                        'storefrontCart.subtotal',
                        (int) $expected->subtotal,
                    )
                    ->has(
                        'storefrontCart.items',
                    ),
            );
    }

    public function test_guest_storefront_shares_null_authenticated_user(): void
    {
        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'auth.user',
                        null,
                    ),
            );
    }

    public function test_authenticated_storefront_shares_account_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
        ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route('home'),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'auth.user.id',
                        $user->id,
                    )
                    ->where(
                        'auth.user.name',
                        'Jane Customer',
                    )
                    ->where(
                        'auth.user.email',
                        'jane@example.com',
                    ),
            );
    }
}
