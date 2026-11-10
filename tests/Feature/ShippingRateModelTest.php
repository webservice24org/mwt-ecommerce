<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shipping\Queries\AvailableShippingRatesQuery;
use App\Domain\Shipping\Services\ShippingRateCalculator;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ShippingRateModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_method_can_have_rates(): void
    {
        $method = ShippingMethod::factory()
            ->create();

        ShippingRate::factory()
            ->count(2)
            ->for($method)
            ->create();

        $this->assertCount(
            2,
            $method->rates,
        );
    }

    public function test_flat_shipping_rate_returns_configured_amount(): void
    {
        $rate = ShippingRate::factory()
            ->create([
                'amount' => 80,
            ]);

        $quote = app(
            ShippingRateCalculator::class,
        )->quote(
            rate: $rate,
            orderSubtotal: 1000,
        );

        $this->assertSame(
            80,
            $quote->amount,
        );

        $this->assertFalse(
            $quote->free,
        );
    }

    public function test_rate_becomes_free_at_threshold(): void
    {
        $rate = ShippingRate::factory()
            ->create([
                'amount' => 80,
                'free_shipping_threshold' => 1500,
            ]);

        $quote = app(
            ShippingRateCalculator::class,
        )->quote(
            rate: $rate,
            orderSubtotal: 1500,
        );

        $this->assertSame(
            0,
            $quote->amount,
        );

        $this->assertTrue(
            $quote->free,
        );
    }

    public function test_rate_remains_paid_below_free_threshold(): void
    {
        $rate = ShippingRate::factory()
            ->create([
                'amount' => 80,
                'free_shipping_threshold' => 1500,
            ]);

        $quote = app(
            ShippingRateCalculator::class,
        )->quote(
            rate: $rate,
            orderSubtotal: 1499,
        );

        $this->assertSame(
            80,
            $quote->amount,
        );

        $this->assertFalse(
            $quote->free,
        );
    }

    public function test_inactive_rate_cannot_be_quoted(): void
    {
        $rate = ShippingRate::factory()
            ->create([
                'is_active' => false,
            ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            ShippingRateCalculator::class,
        )->quote(
            rate: $rate,
            orderSubtotal: 1000,
        );
    }

    public function test_inactive_shipping_method_cannot_be_quoted(): void
    {
        $method = ShippingMethod::factory()
            ->create([
                'is_active' => false,
            ]);

        $rate = ShippingRate::factory()
            ->for($method)
            ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            ShippingRateCalculator::class,
        )->quote(
            rate: $rate,
            orderSubtotal: 1000,
        );
    }

    public function test_minimum_order_amount_is_enforced(): void
    {
        $rate = ShippingRate::factory()
            ->create([
                'min_order_amount' => 500,
            ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            ShippingRateCalculator::class,
        )->quote(
            rate: $rate,
            orderSubtotal: 499,
        );
    }

    public function test_available_rates_query_filters_by_subtotal(): void
    {
        ShippingRate::factory()
            ->create([
                'code' => 'small-order',
                'min_order_amount' => null,
                'max_order_amount' => 1000,
                'priority' => 10,
            ]);

        ShippingRate::factory()
            ->create([
                'code' => 'large-order',
                'min_order_amount' => 1001,
                'max_order_amount' => null,
                'priority' => 20,
            ]);

        $quotes = app(
            AvailableShippingRatesQuery::class,
        )->get(
            orderSubtotal: 800,
        );

        $this->assertCount(
            1,
            $quotes,
        );

        $this->assertSame(
            'small-order',
            $quotes->first()?->rateCode,
        );
    }
}
