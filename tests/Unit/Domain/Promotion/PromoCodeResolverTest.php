<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Promotion;

use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Services\PromoCodeResolver;
use App\Models\PromoCode;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PromoCodeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_promo_code_resolves_its_own_values(): void
    {
        $promoCode = PromoCode::query()->create([
            'code' => 'SAVE100',
            'name' => 'Save 100',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 100,
            'minimum_subtotal' => 500,
            'is_active' => true,
        ]);

        $resolved = app(PromoCodeResolver::class)
            ->resolve($promoCode);

        $this->assertSame(
            DiscountType::Fixed,
            $resolved->discountType,
        );

        $this->assertSame(
            100,
            $resolved->discountValue,
        );

        $this->assertSame(
            500,
            $resolved->minimumSubtotal,
        );

        $this->assertTrue(
            $resolved->isActive,
        );
    }

    public function test_promo_code_inherits_values_from_parent_promotion(): void
    {
        $promotion = Promotion::query()->create([
            'name' => 'Black Friday',
            'slug' => 'black-friday',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 2000,
            'maximum_discount' => 500,
            'minimum_subtotal' => 1000,
            'is_active' => true,
        ]);

        $promoCode = PromoCode::query()->create([
            'promotion_id' => $promotion->id,
            'code' => 'BLACK20',
            'name' => 'Black Friday Code',
            'is_active' => true,
        ]);

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

        $this->assertSame(
            500,
            $resolved->maximumDiscount,
        );

        $this->assertSame(
            1000,
            $resolved->minimumSubtotal,
        );
    }

    public function test_promo_code_values_override_parent_values(): void
    {
        $promotion = Promotion::query()->create([
            'name' => 'Campaign',
            'slug' => 'campaign',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 1000,
            'minimum_subtotal' => 1000,
            'is_active' => true,
        ]);

        $promoCode = PromoCode::query()->create([
            'promotion_id' => $promotion->id,
            'code' => 'VIP20',
            'name' => 'VIP Code',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 2000,
            'minimum_subtotal' => 2500,
            'is_active' => true,
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

    public function test_inactive_parent_makes_child_code_inactive(): void
    {
        $promotion = Promotion::query()->create([
            'name' => 'Disabled Campaign',
            'slug' => 'disabled-campaign',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 100,
            'is_active' => false,
        ]);

        $promoCode = PromoCode::query()->create([
            'promotion_id' => $promotion->id,
            'code' => 'DISABLED100',
            'name' => 'Disabled Code',
            'is_active' => true,
        ]);

        $resolved = app(PromoCodeResolver::class)
            ->resolve($promoCode);

        $this->assertFalse(
            $resolved->isActive,
        );
    }

    public function test_unresolvable_code_throws_domain_exception(): void
    {
        $promoCode = PromoCode::query()->create([
            'code' => 'BROKEN',
            'name' => 'Broken Code',
            'is_active' => true,
        ]);

        $this->expectException(
            LogicException::class,
        );

        app(PromoCodeResolver::class)
            ->resolve($promoCode);
    }
}
