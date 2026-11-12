<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use App\Domain\Cart\Data\CartPricingSummaryData;
use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Cart\Services\CartPromoPricingService;
use App\Domain\Cart\Services\CartPromoSession;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CartPromoPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        session()->flush();
    }

    protected function tearDown(): void
    {
        session()->flush();

        parent::tearDown();
    }

    public function test_cart_without_selected_promo_keeps_subtotal_as_grand_total(): void
    {
        $result =
            $this->calculate(
                subtotal: 5000,
            );

        $this->assertNull(
            $result->promo,
        );

        $this->assertSame(
            0,
            $result->discountTotal,
        );

        $this->assertSame(
            5000,
            $result->grandTotal,
        );
    }

    public function test_valid_fixed_promo_is_calculated_from_authoritative_cart_subtotal(): void
    {
        $promoCode =
            $this->promoCode([
                'code' => 'FIXED1000',

                'discount_type' => 'fixed',

                'discount_value' => 1000,
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,
            );

        $this->assertNotNull(
            $result->promo,
        );

        $this->assertSame(
            $promoCode->id,
            $result
                ->promo
                ?->promoCodeId,
        );

        $this->assertSame(
            'FIXED1000',
            $result
                ->promo
                ?->code,
        );

        $this->assertSame(
            1000,
            $result->discountTotal,
        );

        $this->assertSame(
            4000,
            $result->grandTotal,
        );
    }

    public function test_percentage_promo_uses_basis_points(): void
    {
        $promoCode =
            $this->promoCode([
                'code' => 'SAVE20',

                'discount_type' => 'percentage',

                'discount_value' => 2000,
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,
            );

        $this->assertSame(
            1000,
            $result->discountTotal,
        );

        $this->assertSame(
            4000,
            $result->grandTotal,
        );
    }

    public function test_percentage_promo_respects_maximum_discount(): void
    {
        $promoCode =
            $this->promoCode([
                'discount_type' => 'percentage',

                'discount_value' => 5000,

                'maximum_discount' => 600,
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,
            );

        $this->assertSame(
            600,
            $result->discountTotal,
        );

        $this->assertSame(
            4400,
            $result->grandTotal,
        );
    }

    public function test_discount_never_exceeds_cart_subtotal(): void
    {
        $promoCode =
            $this->promoCode([
                'discount_type' => 'fixed',

                'discount_value' => 10000,
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 2000,
            );

        $this->assertSame(
            2000,
            $result->discountTotal,
        );

        $this->assertSame(
            0,
            $result->grandTotal,
        );
    }

    public function test_missing_selected_promo_is_removed_from_session(): void
    {
        $session =
            app(
                CartPromoSession::class,
            );

        $session->put(
            999999,
        );

        $result =
            $this->calculate(
                subtotal: 5000,
            );

        $this->assertNull(
            $result->promo,
        );

        $this->assertSame(
            0,
            $result->discountTotal,
        );

        $this->assertSame(
            5000,
            $result->grandTotal,
        );

        $this->assertNull(
            $session->promoCodeId(),
        );
    }

    public function test_inactive_selected_promo_is_removed_from_session(): void
    {
        $promoCode =
            $this->promoCode([
                'is_active' => false,
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,
            );

        $this->assertPromoWasCleared(
            result: $result,

            expectedGrandTotal: 5000,
        );
    }

    public function test_expired_selected_promo_is_removed_from_session(): void
    {
        $promoCode =
            $this->promoCode([
                'ends_at' => CarbonImmutable::now()
                    ->subMinute(),
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,

                now: CarbonImmutable::now(),
            );

        $this->assertPromoWasCleared(
            result: $result,

            expectedGrandTotal: 5000,
        );
    }

    public function test_future_selected_promo_is_removed_from_session(): void
    {
        $now =
            CarbonImmutable::now();

        $promoCode =
            $this->promoCode([
                'starts_at' => $now->addHour(),
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,

                now: $now,
            );

        $this->assertPromoWasCleared(
            result: $result,

            expectedGrandTotal: 5000,
        );
    }

    public function test_promo_is_removed_when_cart_falls_below_minimum_subtotal(): void
    {
        $promoCode =
            $this->promoCode([
                'minimum_subtotal' => 5000,
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 4999,
            );

        $this->assertPromoWasCleared(
            result: $result,

            expectedGrandTotal: 4999,
        );
    }

    public function test_exhausted_global_usage_limit_removes_selected_promo(): void
    {
        $promoCode =
            $this->promoCode([
                'usage_limit' => 2,

                'redeemed_count' => 2,
            ]);

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,
            );

        $this->assertPromoWasCleared(
            result: $result,

            expectedGrandTotal: 5000,
        );
    }

    public function test_authenticated_customer_usage_limit_is_revalidated(): void
    {
        $customer =
            User::factory()
                ->create();

        $promoCode =
            $this->promoCode([
                'usage_limit_per_customer' => 1,
            ]);

        $this->createUsage(
            promoCode: $promoCode,

            customer: $customer,
        );

        $this->select(
            $promoCode,
        );

        $result =
            $this->calculate(
                subtotal: 5000,

                userId: $customer->id,
            );

        $this->assertPromoWasCleared(
            result: $result,

            expectedGrandTotal: 5000,
        );
    }

    public function test_guest_skips_per_customer_usage_limit(): void
    {
        $customer =
            User::factory()
                ->create();

        $promoCode =
            $this->promoCode([
                'discount_type' => 'fixed',

                'discount_value' => 500,

                'usage_limit_per_customer' => 1,
            ]);

        $this->createUsage(
            promoCode: $promoCode,

            customer: $customer,
        );

        $this->select(
            $promoCode,
        );

        /*
         * Guest:
         * userId = null.
         *
         * Existing validation rules intentionally
         * skip per-customer limits for guests.
         */
        $result =
            $this->calculate(
                subtotal: 5000,

                userId: null,
            );

        $this->assertNotNull(
            $result->promo,
        );

        $this->assertSame(
            500,
            $result->discountTotal,
        );

        $this->assertSame(
            4500,
            $result->grandTotal,
        );
    }

    public function test_valid_promo_remains_selected_after_pricing(): void
    {
        $promoCode =
            $this->promoCode();

        $this->select(
            $promoCode,
        );

        $this->calculate(
            subtotal: 5000,
        );

        $this->assertSame(
            $promoCode->id,
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    private function calculate(
        int $subtotal,
        ?int $userId = null,
        ?CarbonImmutable $now = null,
    ): CartPricingSummaryData {
        $cart =
            new CartSummaryData(
                items: [],
                itemCount: 0,
                subtotal: $subtotal,
            );

        return app(
            CartPromoPricingService::class,
        )->calculate(
            cart: $cart,

            userId: $userId,

            now: $now,
        );
    }

    private function select(
        PromoCode $promoCode,
    ): void {
        app(
            CartPromoSession::class,
        )->put(
            $promoCode->id,
        );
    }

    private function assertPromoWasCleared(
        CartPricingSummaryData $result,
        int $expectedGrandTotal,
    ): void {
        $this->assertNull(
            $result->promo,
        );

        $this->assertSame(
            0,
            $result->discountTotal,
        );

        $this->assertSame(
            $expectedGrandTotal,
            $result->grandTotal,
        );

        $this->assertNull(
            app(
                CartPromoSession::class,
            )->promoCodeId(),
        );
    }

    private function createUsage(
        PromoCode $promoCode,
        User $customer,
    ): void {
        $order =
            Order::factory()
                ->create([
                    'user_id' => $customer->id,
                ]);

        PromoCodeUsage::query()
            ->create([
                'promo_code_id' => $promoCode->id,

                'user_id' => $customer->id,

                'order_id' => $order->id,

                'code' => $promoCode->code,

                'discount_amount' => 100,

                'used_at' => now(),
            ]);
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

                        'code' => "CART{$sequence}",

                        'name' => "Cart Promo {$sequence}",

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
}
