<?php

declare(strict_types=1);

namespace Tests\Feature\Promotion;

use App\Actions\Promotion\RedeemPromoCodeAction;
use App\Domain\Promotion\Data\PromoValidationContext;
use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Exceptions\PromoRedemptionException;
use App\Domain\Promotion\Services\PromoCodeResolver;
use App\Domain\Promotion\Services\PromoDiscountCalculator;
use App\Domain\Promotion\Services\PromoEligibilityService;
use App\Domain\Promotion\Services\PromoValidationService;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\Promotion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionDomainIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_promo_flows_from_resolution_through_redemption(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'code' => '  save20  ',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 2000,
            'minimum_subtotal' => 500,
            'usage_limit' => 10,
            'usage_limit_per_customer' => 2,
        ]);

        $this->assertSame(
            'SAVE20',
            $promoCode->code,
        );

        $resolved = app(PromoCodeResolver::class)
            ->resolve($promoCode);

        $this->assertSame(
            DiscountType::Percentage,
            $resolved->discountType,
        );

        $this->assertSame(
            2000,
            $resolved->discountValue,
        );

        $eligibility = app(PromoEligibilityService::class)
            ->check(
                promo: $resolved,
                subtotal: 1000,
            );

        $this->assertTrue(
            $eligibility->eligible,
        );

        $validation = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                    userId: $customer->id,
                ),
            );

        $this->assertTrue(
            $validation->eligible,
        );

        $calculation = app(PromoDiscountCalculator::class)
            ->calculate(
                promo: $resolved,
                subtotal: 1000,
            );

        $this->assertSame(
            200,
            $calculation->discountAmount,
        );

        $redemption = app(RedeemPromoCodeAction::class)
            ->execute(
                promoCode: $promoCode,
                order: $order,
                subtotal: 1000,
                userId: $customer->id,
            );

        $this->assertTrue(
            $redemption->redeemedNow,
        );

        $this->assertSame(
            200,
            $redemption->discountAmount,
        );

        $this->assertDatabaseHas(
            'promo_code_usages',
            [
                'promo_code_id' => $promoCode->id,
                'user_id' => $customer->id,
                'order_id' => $order->id,
                'code' => 'SAVE20',
                'discount_amount' => 200,
            ],
        );

        $this->assertSame(
            1,
            $promoCode->fresh()?->redeemed_count,
        );
    }

    public function test_promo_code_inherits_discount_rules_from_parent_promotion(): void
    {
        $promotion = Promotion::query()->create([
            'name' => 'Black Friday',
            'slug' => 'black-friday',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 2500,
            'maximum_discount' => 300,
            'minimum_subtotal' => 1000,
            'is_active' => true,
            'is_stackable' => false,
        ]);

        $promoCode = $this->promoCode([
            'promotion_id' => $promotion->id,
            'code' => 'BLACK25',
            'discount_type' => null,
            'discount_value' => null,
            'maximum_discount' => null,
            'minimum_subtotal' => null,
        ]);

        $resolved = app(PromoCodeResolver::class)
            ->resolve($promoCode);

        $this->assertSame(
            DiscountType::Percentage,
            $resolved->discountType,
        );

        $this->assertSame(
            2500,
            $resolved->discountValue,
        );

        $this->assertSame(
            300,
            $resolved->maximumDiscount,
        );

        $this->assertSame(
            1000,
            $resolved->minimumSubtotal,
        );

        $calculation = app(PromoDiscountCalculator::class)
            ->calculate(
                promo: $resolved,
                subtotal: 2000,
            );

        $this->assertSame(
            500,
            $calculation->requestedDiscount,
        );

        $this->assertSame(
            300,
            $calculation->discountAmount,
        );

        $this->assertTrue(
            $calculation->cappedByMaximumDiscount,
        );
    }

    public function test_child_promo_can_override_parent_discount_values(): void
    {
        $promotion = Promotion::query()->create([
            'name' => 'Campaign',
            'slug' => 'campaign',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 1000,
            'minimum_subtotal' => 1000,
            'is_active' => true,
            'is_stackable' => false,
        ]);

        $promoCode = $this->promoCode([
            'promotion_id' => $promotion->id,
            'code' => 'VIP20',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 2000,
            'minimum_subtotal' => 2500,
        ]);

        $resolved = app(PromoCodeResolver::class)
            ->resolve($promoCode);

        $this->assertSame(
            2000,
            $resolved->discountValue,
        );

        $this->assertSame(
            2500,
            $resolved->minimumSubtotal,
        );
    }

    public function test_inactive_parent_blocks_child_promo_redemption(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promotion = Promotion::query()->create([
            'name' => 'Disabled Campaign',
            'slug' => 'disabled-campaign',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 100,
            'is_active' => false,
            'is_stackable' => false,
        ]);

        $promoCode = $this->promoCode([
            'promotion_id' => $promotion->id,
            'discount_type' => null,
            'discount_value' => null,
        ]);

        try {
            app(RedeemPromoCodeAction::class)
                ->execute(
                    promoCode: $promoCode,
                    order: $order,
                    subtotal: 1000,
                    userId: $customer->id,
                );

            $this->fail(
                'Expected inactive parent promotion to block redemption.',
            );
        } catch (PromoRedemptionException $exception) {
            $this->assertSame(
                'This promo code is inactive.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'promo_code_usages',
            0,
        );

        $this->assertSame(
            0,
            $promoCode->fresh()?->redeemed_count,
        );
    }

    public function test_future_promo_cannot_be_redeemed_early(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'starts_at' => CarbonImmutable::parse(
                '2026-12-01 00:00:00',
            ),
        ]);

        try {
            app(RedeemPromoCodeAction::class)
                ->execute(
                    promoCode: $promoCode,
                    order: $order,
                    subtotal: 1000,
                    userId: $customer->id,
                    now: CarbonImmutable::parse(
                        '2026-11-10 12:00:00',
                    ),
                );

            $this->fail(
                'Expected future promo to be rejected.',
            );
        } catch (PromoRedemptionException $exception) {
            $this->assertSame(
                'This promo code is not active yet.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'promo_code_usages',
            0,
        );
    }

    public function test_expired_promo_cannot_be_redeemed(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'ends_at' => CarbonImmutable::parse(
                '2026-11-01 23:59:59',
            ),
        ]);

        try {
            app(RedeemPromoCodeAction::class)
                ->execute(
                    promoCode: $promoCode,
                    order: $order,
                    subtotal: 1000,
                    userId: $customer->id,
                    now: CarbonImmutable::parse(
                        '2026-11-10 12:00:00',
                    ),
                );

            $this->fail(
                'Expected expired promo to be rejected.',
            );
        } catch (PromoRedemptionException $exception) {
            $this->assertSame(
                'This promo code has expired.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'promo_code_usages',
            0,
        );
    }

    public function test_minimum_subtotal_failure_does_not_mutate_redemption_state(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'minimum_subtotal' => 2000,
        ]);

        try {
            app(RedeemPromoCodeAction::class)
                ->execute(
                    promoCode: $promoCode,
                    order: $order,
                    subtotal: 1999,
                    userId: $customer->id,
                );

            $this->fail(
                'Expected minimum subtotal validation failure.',
            );
        } catch (PromoRedemptionException $exception) {
            $this->assertSame(
                'The minimum order amount for this promo code has not been reached.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'promo_code_usages',
            0,
        );

        $this->assertSame(
            0,
            $promoCode->fresh()?->redeemed_count,
        );
    }

    public function test_global_usage_limit_blocks_additional_redemption(): void
    {
        $firstCustomer = User::factory()->create();

        $secondCustomer = User::factory()->create();

        $firstOrder = Order::factory()->create([
            'user_id' => $firstCustomer->id,
        ]);

        $secondOrder = Order::factory()->create([
            'user_id' => $secondCustomer->id,
        ]);

        $promoCode = $this->promoCode([
            'usage_limit' => 1,
        ]);

        $action = app(
            RedeemPromoCodeAction::class,
        );

        $action->execute(
            promoCode: $promoCode,
            order: $firstOrder,
            subtotal: 1000,
            userId: $firstCustomer->id,
        );

        try {
            $action->execute(
                promoCode: $promoCode,
                order: $secondOrder,
                subtotal: 1000,
                userId: $secondCustomer->id,
            );

            $this->fail(
                'Expected global usage limit to block redemption.',
            );
        } catch (PromoRedemptionException $exception) {
            $this->assertSame(
                'This promo code has reached its usage limit.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'promo_code_usages',
            1,
        );

        $this->assertSame(
            1,
            $promoCode->fresh()?->redeemed_count,
        );
    }

    public function test_customer_usage_limit_blocks_same_customer_but_not_other_customer(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $firstOrder = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secondOrder = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $otherOrder = Order::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $promoCode = $this->promoCode([
            'usage_limit' => 10,
            'usage_limit_per_customer' => 1,
        ]);

        $action = app(
            RedeemPromoCodeAction::class,
        );

        $action->execute(
            promoCode: $promoCode,
            order: $firstOrder,
            subtotal: 1000,
            userId: $customer->id,
        );

        try {
            $action->execute(
                promoCode: $promoCode,
                order: $secondOrder,
                subtotal: 1000,
                userId: $customer->id,
            );

            $this->fail(
                'Expected per-customer usage limit to reject second redemption.',
            );
        } catch (PromoRedemptionException $exception) {
            $this->assertSame(
                'You have already used this promo code the maximum number of times.',
                $exception->getMessage(),
            );
        }

        $otherResult = $action->execute(
            promoCode: $promoCode,
            order: $otherOrder,
            subtotal: 1000,
            userId: $otherCustomer->id,
        );

        $this->assertTrue(
            $otherResult->redeemedNow,
        );

        $this->assertDatabaseCount(
            'promo_code_usages',
            2,
        );

        $this->assertSame(
            2,
            $promoCode->fresh()?->redeemed_count,
        );
    }

    public function test_same_order_retry_is_idempotent_and_does_not_increment_usage_twice(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'usage_limit' => 1,
        ]);

        $action = app(
            RedeemPromoCodeAction::class,
        );

        $first = $action->execute(
            promoCode: $promoCode,
            order: $order,
            subtotal: 1000,
            userId: $customer->id,
        );

        $second = $action->execute(
            promoCode: $promoCode,
            order: $order,
            subtotal: 1000,
            userId: $customer->id,
        );

        $this->assertTrue(
            $first->redeemedNow,
        );

        $this->assertFalse(
            $second->redeemedNow,
        );

        $this->assertSame(
            $first->usage->id,
            $second->usage->id,
        );

        $this->assertSame(
            $first->discountAmount,
            $second->discountAmount,
        );

        $this->assertDatabaseCount(
            'promo_code_usages',
            1,
        );

        $this->assertSame(
            1,
            $promoCode->fresh()?->redeemed_count,
        );
    }

    public function test_fixed_discount_never_reduces_order_below_zero(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 5000,
        ]);

        $result = app(RedeemPromoCodeAction::class)
            ->execute(
                promoCode: $promoCode,
                order: $order,
                subtotal: 1000,
                userId: $customer->id,
            );

        $this->assertSame(
            1000,
            $result->discountAmount,
        );

        $this->assertSame(
            1000,
            $result->usage->discount_amount,
        );
    }

    public function test_percentage_discount_respects_maximum_discount_during_redemption(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 2500,
            'maximum_discount' => 300,
        ]);

        $result = app(RedeemPromoCodeAction::class)
            ->execute(
                promoCode: $promoCode,
                order: $order,
                subtotal: 2000,
                userId: $customer->id,
            );

        $this->assertSame(
            300,
            $result->discountAmount,
        );

        $this->assertDatabaseHas(
            'promo_code_usages',
            [
                'promo_code_id' => $promoCode->id,
                'order_id' => $order->id,
                'discount_amount' => 300,
            ],
        );
    }

    public function test_failed_redemption_does_not_create_partial_audit_state(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'is_active' => false,
        ]);

        try {
            app(RedeemPromoCodeAction::class)
                ->execute(
                    promoCode: $promoCode,
                    order: $order,
                    subtotal: 1000,
                    userId: $customer->id,
                );

            $this->fail(
                'Expected redemption to fail.',
            );
        } catch (PromoRedemptionException) {
            // Expected.
        }

        $this->assertDatabaseMissing(
            'promo_code_usages',
            [
                'promo_code_id' => $promoCode->id,
                'order_id' => $order->id,
            ],
        );

        $this->assertSame(
            0,
            $promoCode->fresh()?->redeemed_count,
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

                    'discount_type' => DiscountType::Percentage,

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
}
