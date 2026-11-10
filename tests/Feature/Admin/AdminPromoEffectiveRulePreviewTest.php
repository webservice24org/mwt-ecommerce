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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminPromoEffectiveRulePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_promo_returns_own_effective_rules(): void
    {
        $promoCode = $this->promoCode([
            'code' => 'FIXED250',
            'discount_type' => 'fixed',
            'discount_value' => 250,
            'maximum_discount' => 200,
            'minimum_subtotal' => 1000,
        ]);

        $this
            ->actingAs(
                $this->admin(),
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
                    )
                    ->where(
                        'promoCodes.data.0.id',
                        $promoCode->id,
                    )
                    ->where(
                        'promoCodes.data.0.effective.discount_type',
                        'fixed',
                    )
                    ->where(
                        'promoCodes.data.0.effective.discount_value',
                        250,
                    )
                    ->where(
                        'promoCodes.data.0.effective.discount_display',
                        'Tk 250',
                    )
                    ->where(
                        'promoCodes.data.0.effective.discount_source',
                        'promo_code',
                    )
                    ->where(
                        'promoCodes.data.0.effective.maximum_discount',
                        200,
                    )
                    ->where(
                        'promoCodes.data.0.effective.minimum_subtotal',
                        1000,
                    )
                    ->where(
                        'promoCodes.data.0.can_delete',
                        true,
                    ),
            );
    }

    public function test_parent_promotion_values_are_inherited(): void
    {
        $promotion = $this->promotion([
            'name' => 'Campaign 15',
            'slug' => 'campaign-15',
            'discount_type' => 'percentage',
            'discount_value' => 1500,
            'maximum_discount' => 500,
            'minimum_subtotal' => 2000,
        ]);

        $promoCode = $this->promoCode([
            'promotion_id' => $promotion->id,
            'code' => 'CAMPAIGN15',

            'discount_type' => null,
            'discount_value' => null,

            'maximum_discount' => null,
            'minimum_subtotal' => null,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.edit',
                    $promoCode,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/PromoCodes/Edit',
                    )
                    ->where(
                        'effective.discount_type',
                        'percentage',
                    )
                    ->where(
                        'effective.discount_value',
                        1500,
                    )
                    ->where(
                        'effective.discount_display',
                        '15%',
                    )
                    ->where(
                        'effective.discount_source',
                        'promotion',
                    )
                    ->where(
                        'effective.maximum_discount',
                        500,
                    )
                    ->where(
                        'effective.maximum_discount_source',
                        'promotion',
                    )
                    ->where(
                        'effective.minimum_subtotal',
                        2000,
                    )
                    ->where(
                        'effective.minimum_subtotal_source',
                        'promotion',
                    ),
            );
    }

    public function test_child_override_wins_over_parent_promotion(): void
    {
        $promotion = $this->promotion([
            'discount_type' => 'percentage',
            'discount_value' => 1000,
            'minimum_subtotal' => 1000,
        ]);

        $promoCode = $this->promoCode([
            'promotion_id' => $promotion->id,

            'discount_type' => 'percentage',
            'discount_value' => 2500,

            'minimum_subtotal' => 3000,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.edit',
                    $promoCode,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'effective.discount_value',
                        2500,
                    )
                    ->where(
                        'effective.discount_display',
                        '25%',
                    )
                    ->where(
                        'effective.discount_source',
                        'promo_code',
                    )
                    ->where(
                        'effective.minimum_subtotal',
                        3000,
                    )
                    ->where(
                        'effective.minimum_subtotal_source',
                        'promo_code',
                    ),
            );
    }

    public function test_inactive_parent_makes_effective_promo_inactive(): void
    {
        $promotion = $this->promotion([
            'is_active' => false,
        ]);

        $promoCode = $this->promoCode([
            'promotion_id' => $promotion->id,

            'discount_type' => null,
            'discount_value' => null,
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.edit',
                    $promoCode,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'effective.is_active',
                        false,
                    )
                    ->where(
                        'effective.status',
                        'inactive',
                    )
                    ->where(
                        'effective.status_label',
                        'Inactive',
                    ),
            );
    }

    public function test_future_promo_status_is_scheduled(): void
    {
        $promoCode = $this->promoCode([
            'starts_at' => CarbonImmutable::now()
                ->addDays(10),
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.edit',
                    $promoCode,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'effective.status',
                        'scheduled',
                    )
                    ->where(
                        'effective.status_label',
                        'Scheduled',
                    ),
            );
    }

    public function test_expired_promo_status_is_expired(): void
    {
        $promoCode = $this->promoCode([
            'ends_at' => CarbonImmutable::now()
                ->subDay(),
        ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.edit',
                    $promoCode,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'effective.status',
                        'expired',
                    )
                    ->where(
                        'effective.status_label',
                        'Expired',
                    ),
            );
    }

    public function test_redeemed_promo_code_exposes_can_delete_false(): void
    {
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

        PromoCodeUsage::query()
            ->create([
                'promo_code_id' => $promoCode->id,

                'user_id' => $customer->id,

                'order_id' => $order->id,

                'code' => $promoCode->code,

                'discount_amount' => 200,

                'used_at' => now(),
            ]);

        $this
            ->actingAs(
                $this->admin(),
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
                    ->where(
                        'promoCodes.data.0.id',
                        $promoCode->id,
                    )
                    ->where(
                        'promoCodes.data.0.usages_count',
                        1,
                    )
                    ->where(
                        'promoCodes.data.0.can_delete',
                        false,
                    ),
            );

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->get(
                route(
                    'admin.promo-codes.edit',
                    $promoCode,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'canDelete',
                        false,
                    ),
            );
    }

    public function test_unused_promo_code_exposes_can_delete_true(): void
    {
        $promoCode =
            $this->promoCode();

        $this
            ->actingAs(
                $this->admin(),
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
                    ->where(
                        'promoCodes.data.0.id',
                        $promoCode->id,
                    )
                    ->where(
                        'promoCodes.data.0.usages_count',
                        0,
                    )
                    ->where(
                        'promoCodes.data.0.can_delete',
                        true,
                    ),
            );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function promotion(
        array $overrides = [],
    ): Promotion {
        return Promotion::query()
            ->create(
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
        return PromoCode::query()
            ->create(
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
        return Admin::factory()
            ->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);
    }
}
