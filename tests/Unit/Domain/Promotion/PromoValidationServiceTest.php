<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Promotion;

use App\Domain\Promotion\Data\PromoValidationContext;
use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Services\PromoValidationService;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_promo_with_no_usage_limits_is_valid(): void
    {
        $promoCode = $this->promoCode();

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                    userId: null,
                    now: CarbonImmutable::parse(
                        '2026-11-10 12:00:00',
                    ),
                ),
            );

        $this->assertTrue(
            $result->eligible,
        );

        $this->assertNull(
            $result->reason,
        );
    }

    public function test_global_usage_limit_not_reached_is_valid(): void
    {
        $promoCode = $this->promoCode([
            'usage_limit' => 10,
            'redeemed_count' => 9,
        ]);

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                ),
            );

        $this->assertTrue(
            $result->eligible,
        );
    }

    public function test_global_usage_limit_reached_is_rejected(): void
    {
        $promoCode = $this->promoCode([
            'usage_limit' => 10,
            'redeemed_count' => 10,
        ]);

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                ),
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'This promo code has reached its usage limit.',
            $result->reason,
        );
    }

    public function test_per_customer_usage_limit_not_reached_is_valid(): void
    {
        $customer = User::factory()->create();

        $promoCode = $this->promoCode([
            'usage_limit_per_customer' => 2,
        ]);

        $this->recordUsage(
            promoCode: $promoCode,
            customer: $customer,
        );

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                    userId: $customer->id,
                ),
            );

        $this->assertTrue(
            $result->eligible,
        );
    }

    public function test_per_customer_usage_limit_reached_is_rejected(): void
    {
        $customer = User::factory()->create();

        $promoCode = $this->promoCode([
            'usage_limit_per_customer' => 2,
        ]);

        $this->recordUsage(
            promoCode: $promoCode,
            customer: $customer,
        );

        $this->recordUsage(
            promoCode: $promoCode,
            customer: $customer,
        );

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                    userId: $customer->id,
                ),
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'You have already used this promo code the maximum number of times.',
            $result->reason,
        );
    }

    public function test_guest_skips_customer_specific_usage_limit(): void
    {
        $promoCode = $this->promoCode([
            'usage_limit_per_customer' => 1,
        ]);

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                    userId: null,
                ),
            );

        $this->assertTrue(
            $result->eligible,
        );
    }

    public function test_inactive_promo_is_rejected(): void
    {
        $promoCode = $this->promoCode([
            'is_active' => false,
        ]);

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                ),
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'This promo code is inactive.',
            $result->reason,
        );
    }

    public function test_expired_promo_is_rejected(): void
    {
        $promoCode = $this->promoCode([
            'ends_at' => CarbonImmutable::parse(
                '2026-11-01 23:59:59',
            ),
        ]);

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1000,
                    now: CarbonImmutable::parse(
                        '2026-11-10 12:00:00',
                    ),
                ),
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'This promo code has expired.',
            $result->reason,
        );
    }

    public function test_minimum_subtotal_failure_is_rejected(): void
    {
        $promoCode = $this->promoCode([
            'minimum_subtotal' => 2000,
        ]);

        $result = app(PromoValidationService::class)
            ->validate(
                promoCode: $promoCode,
                context: new PromoValidationContext(
                    subtotal: 1999,
                ),
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertSame(
            'The minimum order amount for this promo code has not been reached.',
            $result->reason,
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

    private function recordUsage(
        PromoCode $promoCode,
        User $customer,
    ): PromoCodeUsage {
        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        return PromoCodeUsage::query()->create([
            'promo_code_id' => $promoCode->id,
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'code' => $promoCode->code,
            'discount_amount' => 100,
            'used_at' => now(),
        ]);
    }
}
