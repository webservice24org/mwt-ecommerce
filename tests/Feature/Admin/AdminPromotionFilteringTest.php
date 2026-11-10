<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\PromoCode;
use App\Models\Promotion;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminPromotionFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_search_by_name(): void
    {
        $this->promotion([
            'name' => 'Black Friday Campaign',
            'slug' => 'black-friday',
        ]);

        $this->promotion([
            'name' => 'Summer Campaign',
            'slug' => 'summer-campaign',
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'search' => 'Black Friday',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Promotions/Index',
                    )
                    ->has(
                        'promotions.data',
                        1,
                    )
                    ->where(
                        'promotions.data.0.name',
                        'Black Friday Campaign',
                    )
                    ->where(
                        'filters.search',
                        'Black Friday',
                    ),
            );
    }

    public function test_promotion_search_by_slug(): void
    {
        $this->promotion([
            'name' => 'First Campaign',
            'slug' => 'special-winter-offer',
        ]);

        $this->promotion([
            'name' => 'Second Campaign',
            'slug' => 'summer-offer',
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'search' => 'winter',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promotions.data',
                        1,
                    )
                    ->where(
                        'promotions.data.0.slug',
                        'special-winter-offer',
                    ),
            );
    }

    public function test_promotion_active_filter(): void
    {
        $now = CarbonImmutable::now();

        $this->promotion([
            'name' => 'Active Promotion',
            'slug' => 'active-promotion',
            'is_active' => true,
            'starts_at' => $now->subDay(),
            'ends_at' => $now->addDay(),
        ]);

        $this->promotion([
            'name' => 'Inactive Promotion',
            'slug' => 'inactive-promotion',
            'is_active' => false,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'status' => 'active',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promotions.data',
                        1,
                    )
                    ->where(
                        'promotions.data.0.name',
                        'Active Promotion',
                    )
                    ->where(
                        'filters.status',
                        'active',
                    ),
            );
    }

    public function test_promotion_scheduled_filter(): void
    {
        $now = CarbonImmutable::now();

        $this->promotion([
            'name' => 'Scheduled Promotion',
            'slug' => 'scheduled-promotion',
            'is_active' => true,
            'starts_at' => $now->addDays(5),
        ]);

        $this->promotion([
            'name' => 'Current Promotion',
            'slug' => 'current-promotion',
            'is_active' => true,
            'starts_at' => $now->subDay(),
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'status' => 'scheduled',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promotions.data',
                        1,
                    )
                    ->where(
                        'promotions.data.0.name',
                        'Scheduled Promotion',
                    ),
            );
    }

    public function test_promotion_expired_filter(): void
    {
        $now = CarbonImmutable::now();

        $this->promotion([
            'name' => 'Expired Promotion',
            'slug' => 'expired-promotion',
            'is_active' => true,
            'ends_at' => $now->subDay(),
        ]);

        $this->promotion([
            'name' => 'Current Promotion',
            'slug' => 'current-promotion',
            'is_active' => true,
            'ends_at' => $now->addDay(),
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'status' => 'expired',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promotions.data',
                        1,
                    )
                    ->where(
                        'promotions.data.0.name',
                        'Expired Promotion',
                    ),
            );
    }

    public function test_promotion_discount_type_filter(): void
    {
        $this->promotion([
            'name' => 'Fixed Campaign',
            'slug' => 'fixed-campaign',
            'discount_type' => 'fixed',
            'discount_value' => 100,
        ]);

        $this->promotion([
            'name' => 'Percentage Campaign',
            'slug' => 'percentage-campaign',
            'discount_type' => 'percentage',
            'discount_value' => 1000,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'discount_type' => 'fixed',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promotions.data',
                        1,
                    )
                    ->where(
                        'promotions.data.0.name',
                        'Fixed Campaign',
                    ),
            );
    }

    public function test_promotion_stackable_filter(): void
    {
        $this->promotion([
            'name' => 'Stackable Campaign',
            'slug' => 'stackable-campaign',
            'is_stackable' => true,
        ]);

        $this->promotion([
            'name' => 'Non Stackable Campaign',
            'slug' => 'non-stackable-campaign',
            'is_stackable' => false,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'stackable' => 'yes',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promotions.data',
                        1,
                    )
                    ->where(
                        'promotions.data.0.name',
                        'Stackable Campaign',
                    ),
            );
    }

    public function test_promo_search_by_code(): void
    {
        $this->promoCode([
            'code' => 'BLACK20',
            'name' => 'Black Discount',
        ]);

        $this->promoCode([
            'code' => 'SUMMER10',
            'name' => 'Summer Discount',
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'search' => 'BLACK20',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'BLACK20',
                    ),
            );
    }

    public function test_promo_search_by_name(): void
    {
        $this->promoCode([
            'code' => 'FIRST10',
            'name' => 'VIP Customer Offer',
        ]);

        $this->promoCode([
            'code' => 'SECOND10',
            'name' => 'General Customer Offer',
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'search' => 'VIP',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'FIRST10',
                    ),
            );
    }

    public function test_promo_standalone_filter(): void
    {
        $promotion = $this->promotion();

        $this->promoCode([
            'code' => 'STANDALONE',
            'promotion_id' => null,
        ]);

        $this->promoCode([
            'code' => 'LINKED',
            'promotion_id' => $promotion->id,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'promotion' => 'standalone',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'STANDALONE',
                    ),
            );
    }

    public function test_promo_parent_promotion_filter(): void
    {
        $firstPromotion = $this->promotion([
            'name' => 'First Campaign',
            'slug' => 'first-campaign',
        ]);

        $secondPromotion = $this->promotion([
            'name' => 'Second Campaign',
            'slug' => 'second-campaign',
        ]);

        $this->promoCode([
            'code' => 'FIRSTCODE',
            'promotion_id' => $firstPromotion->id,
        ]);

        $this->promoCode([
            'code' => 'SECONDCODE',
            'promotion_id' => $secondPromotion->id,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'promotion' => (string) $firstPromotion->id,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'FIRSTCODE',
                    ),
            );
    }

    public function test_promo_inherited_filter(): void
    {
        $promotion = $this->promotion();

        $this->promoCode([
            'code' => 'INHERITED',
            'promotion_id' => $promotion->id,
            'discount_type' => null,
            'discount_value' => null,
        ]);

        $this->promoCode([
            'code' => 'OVERRIDE',
            'promotion_id' => $promotion->id,
            'discount_type' => 'fixed',
            'discount_value' => 100,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'source' => 'inherited',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'INHERITED',
                    ),
            );
    }

    public function test_promo_override_filter(): void
    {
        $promotion = $this->promotion();

        $this->promoCode([
            'code' => 'INHERITED',
            'promotion_id' => $promotion->id,
            'discount_type' => null,
            'discount_value' => null,
        ]);

        $this->promoCode([
            'code' => 'OVERRIDE',
            'promotion_id' => $promotion->id,
            'discount_type' => 'percentage',
            'discount_value' => 2500,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'source' => 'override',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'OVERRIDE',
                    ),
            );
    }

    public function test_promo_unused_filter(): void
    {
        $this->promoCode([
            'code' => 'UNUSED',
            'redeemed_count' => 0,
        ]);

        $this->promoCode([
            'code' => 'USED',
            'redeemed_count' => 1,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'usage' => 'unused',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'UNUSED',
                    ),
            );
    }

    public function test_promo_used_filter(): void
    {
        $this->promoCode([
            'code' => 'UNUSED',
            'redeemed_count' => 0,
        ]);

        $this->promoCode([
            'code' => 'USED',
            'redeemed_count' => 1,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'usage' => 'used',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'USED',
                    ),
            );
    }

    public function test_promo_exhausted_filter(): void
    {
        $this->promoCode([
            'code' => 'EXHAUSTED',
            'usage_limit' => 2,
            'redeemed_count' => 2,
        ]);

        $this->promoCode([
            'code' => 'AVAILABLE',
            'usage_limit' => 5,
            'redeemed_count' => 1,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'usage' => 'exhausted',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.code',
                        'EXHAUSTED',
                    ),
            );
    }

    public function test_query_strings_survive_promotion_pagination(): void
    {
        foreach (range(1, 15) as $index) {
            $this->promotion([
                'name' => "Search Campaign {$index}",
                'slug' => "search-campaign-{$index}",
            ]);
        }

        $response = $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'search' => 'Search',
                        'per_page' => 10,
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promotions.data',
                        10,
                    )
                    ->where(
                        'filters.search',
                        'Search',
                    )
                    ->where(
                        'filters.per_page',
                        10,
                    )
                    ->where(
                        'promotions.links.2.url',
                        fn ($url) => is_string($url) &&
                            str_contains(
                                $url,
                                'search=Search',
                            ) &&
                            str_contains(
                                $url,
                                'per_page=10',
                            ),
                    ),
            );
    }

    public function test_query_strings_survive_promo_code_pagination(): void
    {
        foreach (range(1, 15) as $index) {
            $this->promoCode([
                'code' => "SEARCH{$index}",
                'name' => "Search Promo {$index}",
            ]);
        }

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'search' => 'Search',
                        'per_page' => 10,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'promoCodes.data',
                        10,
                    )
                    ->where(
                        'filters.search',
                        'Search',
                    )
                    ->where(
                        'filters.per_page',
                        10,
                    )
                    ->where(
                        'promoCodes.links.2.url',
                        fn ($url) => is_string($url) &&
                            str_contains(
                                $url,
                                'search=Search',
                            ) &&
                            str_contains(
                                $url,
                                'per_page=10',
                            ),
                    ),
            );
    }

    public function test_invalid_promotion_filters_are_rejected(): void
    {
        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                    [
                        'status' => 'invalid-status',
                        'discount_type' => 'invalid-type',
                        'stackable' => 'maybe',
                        'per_page' => 999,
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'status',
                'discount_type',
                'stackable',
                'per_page',
            ]);
    }

    public function test_invalid_promo_code_filters_are_rejected(): void
    {
        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                    [
                        'status' => 'invalid-status',
                        'discount_type' => 'invalid-type',
                        'source' => 'invalid-source',
                        'usage' => 'invalid-usage',
                        'per_page' => 999,
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'status',
                'discount_type',
                'source',
                'usage',
                'per_page',
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function promotion(
        array $overrides = [],
    ): Promotion {
        static $sequence = 0;

        $sequence++;

        return Promotion::query()->create(
            array_merge(
                [
                    'name' => "Test Campaign {$sequence}",

                    'slug' => "test-campaign-{$sequence}",

                    'description' => null,

                    'discount_type' => 'percentage',

                    'discount_value' => 2000,

                    'maximum_discount' => null,

                    'minimum_subtotal' => null,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
                $overrides,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function promoCode(
        array $overrides = [],
    ): PromoCode {
        static $sequence = 0;

        $sequence++;

        return PromoCode::query()->create(
            array_merge(
                [
                    'promotion_id' => null,

                    'code' => "CODE{$sequence}",

                    'name' => "Promo Code {$sequence}",

                    'description' => null,

                    'discount_type' => 'percentage',

                    'discount_value' => 2000,

                    'maximum_discount' => null,

                    'minimum_subtotal' => null,

                    'usage_limit' => null,

                    'usage_limit_per_customer' => null,

                    'redeemed_count' => 0,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
                $overrides,
            ),
        );
    }

    private function admin(): Admin
    {
        return Admin::factory()->create([
            'role' => AdminRole::Admin,

            'is_active' => true,
        ]);
    }
}
