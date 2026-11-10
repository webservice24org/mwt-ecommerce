<?php

declare(strict_types=1);

namespace Tests\Feature\Promotion;

use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_tables_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('promotions'),
        );

        $this->assertTrue(
            Schema::hasTable('promo_codes'),
        );

        $this->assertTrue(
            Schema::hasTable('promo_code_usages'),
        );
    }

    public function test_promo_code_is_normalized_before_storage(): void
    {
        $promoCode = PromoCode::query()->create([
            'code' => '  save20  ',
            'name' => 'Save 20',
            'discount_type' => 'percentage',
            'discount_value' => 2000,
            'is_active' => true,
        ]);

        $this->assertSame(
            'SAVE20',
            $promoCode->code,
        );
    }

    public function test_promo_code_can_belong_to_promotion(): void
    {
        $promotion = Promotion::query()->create([
            'name' => 'Black Friday',
            'slug' => 'black-friday',
            'discount_type' => 'percentage',
            'discount_value' => 2000,
            'is_active' => true,
        ]);

        $promoCode = PromoCode::query()->create([
            'promotion_id' => $promotion->id,
            'code' => 'BLACK20',
            'name' => 'Black Friday 20%',
            'is_active' => true,
        ]);

        $this->assertTrue(
            $promoCode->promotion->is($promotion),
        );

        $this->assertTrue(
            $promotion
                ->promoCodes
                ->contains($promoCode),
        );
    }

    public function test_promo_code_usage_records_order_and_customer_redemption(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $promoCode = PromoCode::query()->create([
            'code' => 'WELCOME100',
            'name' => 'Welcome discount',
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'is_active' => true,
        ]);

        $usage = PromoCodeUsage::query()->create([
            'promo_code_id' => $promoCode->id,
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'code' => $promoCode->code,
            'discount_amount' => 100,
            'used_at' => now(),
        ]);

        $this->assertTrue(
            $usage->promoCode->is($promoCode),
        );

        $this->assertTrue(
            $usage->user->is($customer),
        );

        $this->assertTrue(
            $usage->order->is($order),
        );
    }
}
