<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Promotion\Services\PromotionDeletionGuard;
use App\Models\Admin;
use App\Models\PromoCode;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminPromotionDeletionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlinked_promotion_can_be_deleted(): void
    {
        $promotion =
            $this->promotion([
                'name' => 'Unused Campaign',

                'slug' => 'unused-campaign',
            ]);

        $this
            ->actingAs(
                $this->admin(),
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
            )
            ->assertSessionHas(
                'success',
                'Promotion deleted successfully.',
            );

        $this->assertDatabaseMissing(
            'promotions',
            [
                'id' => $promotion->id,
            ],
        );
    }

    public function test_promotion_with_inherited_promo_code_cannot_be_deleted(): void
    {
        $promotion =
            $this->promotion();

        $promoCode =
            $this->promoCode([
                'promotion_id' => $promotion->id,

                'code' => 'INHERITED20',

                'discount_type' => null,

                'discount_value' => null,
            ]);

        $response = $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->from(
                route(
                    'admin.promotions.index',
                ),
            )
            ->delete(
                route(
                    'admin.promotions.destroy',
                    $promotion,
                ),
            );

        $response
            ->assertRedirect(
                route(
                    'admin.promotions.index',
                ),
            )
            ->assertSessionHas(
                'error',
                'This promotion cannot be deleted because 1 linked promo code is still using it. Detach the promo code first.',
            );

        $this->assertDatabaseHas(
            'promotions',
            [
                'id' => $promotion->id,
            ],
        );

        $this->assertDatabaseHas(
            'promo_codes',
            [
                'id' => $promoCode->id,

                'promotion_id' => $promotion->id,
            ],
        );
    }

    public function test_fully_overridden_but_still_linked_promo_code_blocks_deletion(): void
    {
        $promotion =
            $this->promotion();

        $promoCode =
            $this->promoCode([
                'promotion_id' => $promotion->id,

                'code' => 'OVERRIDE20',

                'discount_type' => 'percentage',

                'discount_value' => 2000,

                'maximum_discount' => 500,

                'minimum_subtotal' => 1000,

                'starts_at' => now()->subDay(),

                'ends_at' => now()->addDays(30),

                'is_active' => true,

                'is_stackable' => false,
            ]);

        $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->from(
                route(
                    'admin.promotions.index',
                ),
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
            )
            ->assertSessionHas(
                'error',
            );

        $this->assertDatabaseHas(
            'promotions',
            [
                'id' => $promotion->id,
            ],
        );

        $this->assertDatabaseHas(
            'promo_codes',
            [
                'id' => $promoCode->id,

                'promotion_id' => $promotion->id,
            ],
        );
    }

    public function test_promotion_can_be_deleted_after_promo_code_is_safely_detached(): void
    {
        $promotion =
            $this->promotion();

        $promoCode =
            $this->promoCode([
                'promotion_id' => $promotion->id,

                'code' => 'DETACH20',

                'discount_type' => 'percentage',

                'discount_value' => 2000,

                'maximum_discount' => 500,

                'minimum_subtotal' => 1000,
            ]);

        $promoCode->update([
            'promotion_id' => null,

            'discount_type' => 'percentage',

            'discount_value' => 2000,

            'maximum_discount' => 500,

            'minimum_subtotal' => 1000,
        ]);

        $this
            ->actingAs(
                $this->admin(),
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
            )
            ->assertSessionHas(
                'success',
                'Promotion deleted successfully.',
            );

        $this->assertDatabaseMissing(
            'promotions',
            [
                'id' => $promotion->id,
            ],
        );

        $this->assertDatabaseHas(
            'promo_codes',
            [
                'id' => $promoCode->id,

                'promotion_id' => null,

                'discount_type' => 'percentage',

                'discount_value' => 2000,
            ],
        );
    }

    public function test_mixed_linked_promo_codes_block_promotion_deletion(): void
    {
        $promotion =
            $this->promotion();

        $this->promoCode([
            'promotion_id' => $promotion->id,

            'code' => 'INHERITED',

            'discount_type' => null,

            'discount_value' => null,
        ]);

        $this->promoCode([
            'promotion_id' => $promotion->id,

            'code' => 'OVERRIDE',

            'discount_type' => 'fixed',

            'discount_value' => 250,
        ]);

        $response = $this
            ->actingAs(
                $this->admin(),
                'admin',
            )
            ->from(
                route(
                    'admin.promotions.index',
                ),
            )
            ->delete(
                route(
                    'admin.promotions.destroy',
                    $promotion,
                ),
            );

        $response
            ->assertRedirect(
                route(
                    'admin.promotions.index',
                ),
            )
            ->assertSessionHas(
                'error',
                'This promotion cannot be deleted because 2 linked promo codes are still using it. Detach the promo codes first.',
            );

        $this->assertDatabaseHas(
            'promotions',
            [
                'id' => $promotion->id,
            ],
        );
    }

    public function test_promotions_index_exposes_deletion_integrity_state(): void
    {
        $blocked =
            $this->promotion([
                'name' => 'Blocked Campaign',

                'slug' => 'blocked-campaign',
            ]);

        $deletable =
            $this->promotion([
                'name' => 'Deletable Campaign',

                'slug' => 'deletable-campaign',
            ]);

        $this->promoCode([
            'promotion_id' => $blocked->id,

            'code' => 'BLOCKED20',

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
                    'admin.promotions.index',
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
                        2,
                    )
                    ->where(
                        'promotions.data.0.id',
                        $deletable->id,
                    )
                    ->where(
                        'promotions.data.0.can_delete',
                        true,
                    )
                    ->where(
                        'promotions.data.0.delete_block_reason',
                        null,
                    )
                    ->where(
                        'promotions.data.1.id',
                        $blocked->id,
                    )
                    ->where(
                        'promotions.data.1.can_delete',
                        false,
                    )
                    ->where(
                        'promotions.data.1.promo_codes_count',
                        1,
                    )
                    ->where(
                        'promotions.data.1.delete_block_reason',
                        fn (
                            mixed $reason,
                        ): bool => is_string(
                            $reason,
                        ) &&
                            str_contains(
                                $reason,
                                '1 linked promo code',
                            ),
                    ),
            );
    }

    public function test_deletion_guard_reports_linked_promo_code_count(): void
    {
        $promotion =
            $this->promotion();

        $this->promoCode([
            'promotion_id' => $promotion->id,

            'code' => 'FIRST20',
        ]);

        $this->promoCode([
            'promotion_id' => $promotion->id,

            'code' => 'SECOND20',
        ]);

        /** @var PromotionDeletionGuard $guard */
        $guard = app(
            PromotionDeletionGuard::class,
        );

        $this->assertSame(
            2,
            $guard->linkedPromoCodeCount(
                $promotion,
            ),
        );

        $this->assertFalse(
            $guard->canDelete(
                $promotion,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function promotion(
        array $overrides = [],
    ): Promotion {
        static $sequence = 0;

        $sequence++;

        return Promotion::query()
            ->create(
                array_merge(
                    [
                        'name' => "Promotion {$sequence}",

                        'slug' => "promotion-{$sequence}",

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

        return PromoCode::query()
            ->create(
                array_merge(
                    [
                        'promotion_id' => null,

                        'code' => "PROMO{$sequence}",

                        'name' => "Promo {$sequence}",

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
