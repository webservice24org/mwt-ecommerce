<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminPromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_promotions_index(): void
    {
        $admin = $this->admin();

        Promotion::query()->create([
            'name' => 'Black Friday',
            'slug' => 'black-friday',
            'discount_type' => 'percentage',
            'discount_value' => 2000,
            'is_active' => true,
            'is_stackable' => false,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.promotions.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Promotions/Index',
                        false,
                    )
                    ->has(
                        'promotions.data',
                        1,
                    ),
            );
    }

    public function test_admin_can_create_promotion(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.promotions.store',
                ),
                [
                    'name' => 'Black Friday',
                    'slug' => 'Black Friday 2026',
                    'description' => 'Black Friday promotional campaign.',

                    'discount_type' => 'percentage',

                    'discount_value' => 2000,

                    'maximum_discount' => 500,

                    'minimum_subtotal' => 1000,

                    'starts_at' => '2026-11-20 00:00:00',

                    'ends_at' => '2026-11-30 23:59:59',

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.promotions.index',
                ),
            );

        $this->assertDatabaseHas(
            'promotions',
            [
                'name' => 'Black Friday',

                'slug' => 'black-friday-2026',

                'discount_type' => 'percentage',

                'discount_value' => 2000,

                'maximum_discount' => 500,

                'minimum_subtotal' => 1000,

                'is_active' => true,
            ],
        );
    }

    public function test_admin_can_update_promotion(): void
    {
        $admin = $this->admin();

        $promotion = $this->promotion();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.promotions.update',
                    $promotion,
                ),
                [
                    'name' => 'Updated Campaign',

                    'slug' => 'Updated Campaign',

                    'description' => 'Updated promotion.',

                    'discount_type' => 'fixed',

                    'discount_value' => 250,

                    'maximum_discount' => null,

                    'minimum_subtotal' => 1500,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => false,

                    'is_stackable' => false,
                ],
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.promotions.index',
                ),
            );

        $this->assertDatabaseHas(
            'promotions',
            [
                'id' => $promotion->id,

                'name' => 'Updated Campaign',

                'slug' => 'updated-campaign',

                'discount_type' => 'fixed',

                'discount_value' => 250,

                'minimum_subtotal' => 1500,

                'is_active' => false,
            ],
        );
    }

    public function test_admin_can_delete_unused_promotion(): void
    {
        $admin = $this->admin();

        $promotion = $this->promotion();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.promotions.destroy',
                    $promotion,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.promotions.index',
                ),
            );

        $this->assertDatabaseMissing(
            'promotions',
            [
                'id' => $promotion->id,
            ],
        );
    }

    public function test_admin_can_view_promo_codes_index(): void
    {
        $admin = $this->admin();

        $this->promoCode();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/PromoCodes/Index',
                        false,
                    )
                    ->has(
                        'promoCodes.data',
                        1,
                    ),
            );
    }

    public function test_admin_can_create_standalone_promo_code(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.promo-codes.store',
                ),
                [
                    'promotion_id' => null,

                    'code' => '  save20  ',

                    'name' => 'Save Twenty Percent',

                    'description' => 'Standalone promo code.',

                    'discount_type' => 'percentage',

                    'discount_value' => 2000,

                    'maximum_discount' => 500,

                    'minimum_subtotal' => 1000,

                    'usage_limit' => 100,

                    'usage_limit_per_customer' => 1,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.promo-codes.index',
                ),
            );

        $this->assertDatabaseHas(
            'promo_codes',
            [
                'code' => 'SAVE20',

                'name' => 'Save Twenty Percent',

                'promotion_id' => null,

                'discount_type' => 'percentage',

                'discount_value' => 2000,

                'usage_limit' => 100,

                'usage_limit_per_customer' => 1,
            ],
        );
    }

    public function test_admin_can_create_promo_code_inheriting_parent_promotion(): void
    {
        $admin = $this->admin();

        $promotion = $this->promotion([
            'discount_type' => 'percentage',

            'discount_value' => 1500,
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.promo-codes.store',
                ),
                [
                    'promotion_id' => $promotion->id,

                    'code' => 'campaign15',

                    'name' => 'Campaign Code',

                    'description' => null,

                    'discount_type' => null,

                    'discount_value' => null,

                    'maximum_discount' => null,

                    'minimum_subtotal' => null,

                    'usage_limit' => null,

                    'usage_limit_per_customer' => null,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.promo-codes.index',
                ),
            );

        $this->assertDatabaseHas(
            'promo_codes',
            [
                'promotion_id' => $promotion->id,

                'code' => 'CAMPAIGN15',

                'discount_type' => null,

                'discount_value' => null,
            ],
        );
    }

    public function test_promo_code_is_normalized_before_unique_validation(): void
    {
        $admin = $this->admin();

        $this->promoCode([
            'code' => 'SAVE20',
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.promo-codes.store',
                ),
                [
                    'promotion_id' => null,

                    'code' => ' save20 ',

                    'name' => 'Duplicate Code',

                    'description' => null,

                    'discount_type' => 'percentage',

                    'discount_value' => 2000,

                    'maximum_discount' => null,

                    'minimum_subtotal' => null,

                    'usage_limit' => null,

                    'usage_limit_per_customer' => null,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response->assertSessionHasErrors([
            'code',
        ]);

        $this->assertDatabaseCount(
            'promo_codes',
            1,
        );
    }

    public function test_percentage_above_one_hundred_percent_is_rejected(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.promo-codes.store',
                ),
                [
                    'promotion_id' => null,

                    'code' => 'TOO-MUCH',

                    'name' => 'Invalid Percentage',

                    'description' => null,

                    'discount_type' => 'percentage',

                    'discount_value' => 10_001,

                    'maximum_discount' => null,

                    'minimum_subtotal' => null,

                    'usage_limit' => null,

                    'usage_limit_per_customer' => null,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response->assertSessionHasErrors([
            'discount_value',
        ]);

        $this->assertDatabaseMissing(
            'promo_codes',
            [
                'code' => 'TOO-MUCH',
            ],
        );
    }

    public function test_invalid_promo_code_date_range_is_rejected(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.promo-codes.store',
                ),
                [
                    'promotion_id' => null,

                    'code' => 'DATES',

                    'name' => 'Invalid Dates',

                    'description' => null,

                    'discount_type' => 'fixed',

                    'discount_value' => 100,

                    'maximum_discount' => null,

                    'minimum_subtotal' => null,

                    'usage_limit' => null,

                    'usage_limit_per_customer' => null,

                    'starts_at' => '2026-12-10 00:00:00',

                    'ends_at' => '2026-12-01 00:00:00',

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response->assertSessionHasErrors([
            'ends_at',
        ]);
    }

    public function test_per_customer_limit_cannot_exceed_global_usage_limit(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.promo-codes.store',
                ),
                [
                    'promotion_id' => null,

                    'code' => 'LIMITED',

                    'name' => 'Limited Promo',

                    'description' => null,

                    'discount_type' => 'fixed',

                    'discount_value' => 100,

                    'maximum_discount' => null,

                    'minimum_subtotal' => null,

                    'usage_limit' => 2,

                    'usage_limit_per_customer' => 3,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response->assertSessionHasErrors([
            'usage_limit_per_customer',
        ]);
    }

    public function test_admin_can_update_promo_code(): void
    {
        $admin = $this->admin();

        $promoCode = $this->promoCode();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.promo-codes.update',
                    $promoCode,
                ),
                [
                    'promotion_id' => null,

                    'code' => ' updated20 ',

                    'name' => 'Updated Promo',

                    'description' => 'Updated description.',

                    'discount_type' => 'percentage',

                    'discount_value' => 2000,

                    'maximum_discount' => 600,

                    'minimum_subtotal' => 1200,

                    'usage_limit' => 50,

                    'usage_limit_per_customer' => 2,

                    'starts_at' => null,

                    'ends_at' => null,

                    'is_active' => true,

                    'is_stackable' => false,
                ],
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.promo-codes.index',
                ),
            );

        $this->assertDatabaseHas(
            'promo_codes',
            [
                'id' => $promoCode->id,

                'code' => 'UPDATED20',

                'name' => 'Updated Promo',

                /*
                 * Percentage values remain basis points.
                 *
                 * 2000 = 20%
                 */
                'discount_value' => 2000,

                /*
                 * Monetary values are stored in
                 * minor currency units.
                 *
                 * BDT 600  = 60000
                 * BDT 1200 = 120000
                 */
                'maximum_discount' => 60000,

                'minimum_subtotal' => 120000,

                'usage_limit' => 50,

                'usage_limit_per_customer' => 2,
            ],
        );
    }

    public function test_unused_promo_code_can_be_deleted(): void
    {
        $admin = $this->admin();

        $promoCode = $this->promoCode();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.promo-codes.destroy',
                    $promoCode,
                ),
            )
            ->assertRedirect(
                route(
                    'admin.promo-codes.index',
                ),
            );

        $this->assertDatabaseMissing(
            'promo_codes',
            [
                'id' => $promoCode->id,
            ],
        );
    }

    public function test_redeemed_promo_code_cannot_be_deleted(): void
    {
        $admin = $this->admin();

        $customer =
            User::factory()->create();

        $order =
            Order::factory()->create([
                'user_id' => $customer->id,
            ]);

        $promoCode =
            $this->promoCode([
                'redeemed_count' => 1,
            ]);

        PromoCodeUsage::query()->create([
            'promo_code_id' => $promoCode->id,

            'user_id' => $customer->id,

            'order_id' => $order->id,

            'code' => $promoCode->code,

            'discount_amount' => 200,

            'used_at' => now(),
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.promo-codes.destroy',
                    $promoCode,
                ),
            );

        $response->assertSessionHas(
            'error',
            'A promo code with redemption history cannot be deleted. Disable it instead.',
        );

        $this->assertDatabaseHas(
            'promo_codes',
            [
                'id' => $promoCode->id,
            ],
        );
    }

    public function test_guest_cannot_access_admin_promotion_routes(): void
    {
        $this
            ->get(
                route(
                    'admin.promotions.index',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.promo-codes.index',
                ),
            )
            ->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function promotion(
        array $overrides = [],
    ): Promotion {
        return Promotion::query()->create(
            array_merge(
                [
                    'name' => 'Test Campaign',

                    'slug' => 'test-campaign',

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
        return PromoCode::query()->create(
            array_merge(
                [
                    'promotion_id' => null,

                    'code' => 'SAVE20',

                    'name' => 'Save 20',

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
