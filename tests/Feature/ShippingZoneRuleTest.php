<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shipping\Data\ShippingDestinationData;
use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use App\Domain\Shipping\Queries\AvailableShippingRatesQuery;
use App\Domain\Shipping\Services\ShippingZoneMatcher;
use App\Domain\Shipping\Services\ShippingZoneResolver;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShippingZoneRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_matches_rules_using_and_across_types_and_or_within_same_type(): void
    {
        $zone = ShippingZone::factory()
            ->create();

        ShippingZoneRule::factory()
            ->for($zone)
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        ShippingZoneRule::factory()
            ->for($zone)
            ->create([
                'type' => ShippingZoneRuleType::City,

                'value' => 'Dhaka',
            ]);

        ShippingZoneRule::factory()
            ->for($zone)
            ->create([
                'type' => ShippingZoneRuleType::City,

                'value' => 'Gazipur',
            ]);

        $matcher = app(
            ShippingZoneMatcher::class,
        );

        $this->assertTrue(
            $matcher->matches(
                $zone,
                new ShippingDestinationData(
                    countryCode: 'BD',
                    city: 'Dhaka',
                ),
            ),
        );

        $this->assertTrue(
            $matcher->matches(
                $zone,
                new ShippingDestinationData(
                    countryCode: 'BD',
                    city: 'Gazipur',
                ),
            ),
        );

        $this->assertFalse(
            $matcher->matches(
                $zone,
                new ShippingDestinationData(
                    countryCode: 'BD',
                    city: 'Chattogram',
                ),
            ),
        );

        $this->assertFalse(
            $matcher->matches(
                $zone,
                new ShippingDestinationData(
                    countryCode: 'US',
                    city: 'Dhaka',
                ),
            ),
        );
    }

    public function test_matching_is_case_insensitive(): void
    {
        $zone = ShippingZone::factory()
            ->create();

        ShippingZoneRule::factory()
            ->for($zone)
            ->create([
                'type' => ShippingZoneRuleType::City,

                'value' => 'Dhaka',
            ]);

        $this->assertTrue(
            app(
                ShippingZoneMatcher::class,
            )->matches(
                $zone,
                new ShippingDestinationData(
                    countryCode: 'BD',
                    city: 'DHAKA',
                ),
            ),
        );
    }

    public function test_catch_all_zone_matches_any_destination(): void
    {
        $zone = ShippingZone::factory()
            ->create([
                'is_catch_all' => true,
            ]);

        $this->assertTrue(
            app(
                ShippingZoneMatcher::class,
            )->matches(
                $zone,
                new ShippingDestinationData(
                    countryCode: 'US',
                    city: 'New York',
                ),
            ),
        );
    }

    public function test_resolver_uses_zone_priority(): void
    {
        $specific = ShippingZone::factory()
            ->create([
                'code' => 'dhaka',
                'priority' => 10,
            ]);

        ShippingZoneRule::factory()
            ->for($specific)
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        ShippingZoneRule::factory()
            ->for($specific)
            ->create([
                'type' => ShippingZoneRuleType::City,

                'value' => 'Dhaka',
            ]);

        $general = ShippingZone::factory()
            ->create([
                'code' => 'bangladesh',
                'priority' => 20,
            ]);

        ShippingZoneRule::factory()
            ->for($general)
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        $resolved = app(
            ShippingZoneResolver::class,
        )->resolve(
            new ShippingDestinationData(
                countryCode: 'BD',
                city: 'Dhaka',
            ),
        );

        $this->assertSame(
            $specific->id,
            $resolved?->id,
        );
    }

    public function test_rate_can_be_limited_to_a_shipping_zone(): void
    {
        $dhaka = ShippingZone::factory()
            ->create([
                'code' => 'dhaka',
            ]);

        ShippingZoneRule::factory()
            ->for($dhaka)
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        ShippingZoneRule::factory()
            ->for($dhaka)
            ->create([
                'type' => ShippingZoneRuleType::City,

                'value' => 'Dhaka',
            ]);

        $global = ShippingRate::factory()
            ->create([
                'code' => 'global',
                'priority' => 10,
            ]);

        $dhakaRate = ShippingRate::factory()
            ->create([
                'code' => 'dhaka-only',
                'priority' => 20,
            ]);

        $dhakaRate
            ->zones()
            ->attach(
                $dhaka,
            );

        $otherRate = ShippingRate::factory()
            ->create([
                'code' => 'other-zone',
                'priority' => 30,
            ]);

        $otherZone = ShippingZone::factory()
            ->create([
                'code' => 'other',
            ]);

        $otherRate
            ->zones()
            ->attach(
                $otherZone,
            );

        $quotes = app(
            AvailableShippingRatesQuery::class,
        )->get(
            orderSubtotal: 1000,
            destination: new ShippingDestinationData(
                countryCode: 'BD',
                city: 'Dhaka',
            ),
        );

        $codes = $quotes
            ->pluck('rateCode')
            ->all();

        $this->assertContains(
            'global',
            $codes,
        );

        $this->assertContains(
            'dhaka-only',
            $codes,
        );

        $this->assertNotContains(
            'other-zone',
            $codes,
        );
    }

    public function test_without_destination_only_global_rates_are_available(): void
    {
        ShippingRate::factory()
            ->create([
                'code' => 'global',
            ]);

        $zonedRate = ShippingRate::factory()
            ->create([
                'code' => 'zoned',
            ]);

        $zone = ShippingZone::factory()
            ->create();

        $zonedRate
            ->zones()
            ->attach(
                $zone,
            );

        $quotes = app(
            AvailableShippingRatesQuery::class,
        )->get(
            orderSubtotal: 1000,
        );

        $codes = $quotes
            ->pluck('rateCode')
            ->all();

        $this->assertContains(
            'global',
            $codes,
        );

        $this->assertNotContains(
            'zoned',
            $codes,
        );
    }
}
