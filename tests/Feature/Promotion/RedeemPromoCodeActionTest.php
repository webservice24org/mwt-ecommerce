<?php

declare(strict_types=1);

namespace Tests\Feature\Promotion;

use App\Actions\Promotion\RedeemPromoCodeAction;
use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Exceptions\PromoRedemptionException;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeemPromoCodeActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_promo_is_redeemed_atomically(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'usage_limit' => 10,
        ]);

        $result = app(RedeemPromoCodeAction::class)
            ->execute(
                promoCode: $promoCode,
                order: $order,
                subtotal: 1000,
                userId: $customer->id,
                now: CarbonImmutable::parse(
                    '2026-11-10 12:00:00',
                ),
            );

        $this->assertTrue(
            $result->redeemedNow,
        );

        $this->assertSame(
            200,
            $result->discountAmount,
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

    public function test_global_usage_limit_is_rechecked_during_redemption(): void
    {
        $order = Order::factory()->create();

        $promoCode = $this->promoCode([
            'usage_limit' => 1,
            'redeemed_count' => 1,
        ]);

        try {
            app(RedeemPromoCodeAction::class)
                ->execute(
                    promoCode: $promoCode,
                    order: $order,
                    subtotal: 1000,
                );

            $this->fail(
                'Expected promo redemption to fail.',
            );
        } catch (PromoRedemptionException $exception) {
            $this->assertSame(
                'This promo code has reached its usage limit.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'promo_code_usages',
            0,
        );

        $this->assertSame(
            1,
            $promoCode->fresh()?->redeemed_count,
        );
    }

    public function test_customer_usage_limit_is_rechecked_during_redemption(): void
    {
        $customer = User::factory()->create();

        $promoCode = $this->promoCode([
            'usage_limit_per_customer' => 1,
        ]);

        $previousOrder = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        PromoCodeUsage::query()->create([
            'promo_code_id' => $promoCode->id,
            'user_id' => $customer->id,
            'order_id' => $previousOrder->id,
            'code' => $promoCode->code,
            'discount_amount' => 200,
            'used_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this->expectException(
            PromoRedemptionException::class,
        );

        $this->expectExceptionMessage(
            'You have already used this promo code the maximum number of times.',
        );

        app(RedeemPromoCodeAction::class)
            ->execute(
                promoCode: $promoCode,
                order: $order,
                subtotal: 1000,
                userId: $customer->id,
            );
    }

    public function test_same_order_redemption_is_idempotent(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode([
            'usage_limit' => 5,
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

    public function test_failed_redemption_does_not_increment_counter(): void
    {
        $order = Order::factory()->create();

        $promoCode = $this->promoCode([
            'minimum_subtotal' => 2000,
        ]);

        try {
            app(RedeemPromoCodeAction::class)
                ->execute(
                    promoCode: $promoCode,
                    order: $order,
                    subtotal: 1000,
                );

            $this->fail(
                'Expected promo redemption to fail.',
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

    public function test_fixed_discount_cannot_exceed_order_subtotal(): void
    {
        $order = Order::factory()->create();

        $promoCode = $this->promoCode([
            'discount_type' => DiscountType::Fixed,

            'discount_value' => 5000,
        ]);

        $result = app(RedeemPromoCodeAction::class)
            ->execute(
                promoCode: $promoCode,
                order: $order,
                subtotal: 1000,
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

    public function test_customer_must_match_order_customer(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = $this->promoCode();

        $this->expectException(
            PromoRedemptionException::class,
        );

        $this->expectExceptionMessage(
            'Promo redemption customer does not match the order.',
        );

        app(RedeemPromoCodeAction::class)
            ->execute(
                promoCode: $promoCode,
                order: $order,
                subtotal: 1000,
                userId: $otherCustomer->id,
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
